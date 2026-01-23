<?php
namespace App\Audit;
/**
 * Copyright 2025 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\Audit\Interfaces\IAuditStrategy;
use App\Repositories\DoctrineUserRepository;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use OAuth2\IResourceServerContext;
use OAuth2\Models\IClient;
use Services\OAuth2\ResourceServerContext;
use OpenTelemetry\API\Baggage\Baggage;

/**
 * Class AuditEventListener
 * @package App\Audit
 */
class AuditEventListener
{
    private const ROUTE_METHOD_SEPARATOR = '|';

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if (app()->environment('testing')) {
            return;
        }
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        // Strategy selection based on environment configuration
        $strategy = $this->getAuditStrategy($em);
        if (!$strategy) {
            return; // No audit strategy enabled
        }

        $ctx = $this->buildAuditContext();

        try {
            foreach ($uow->getScheduledEntityInsertions() as $entity) {
                $strategy->audit($entity, [], IAuditStrategy::EVENT_ENTITY_CREATION, $ctx);
            }

            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                $strategy->audit($entity, $uow->getEntityChangeSet($entity), IAuditStrategy::EVENT_ENTITY_UPDATE, $ctx);
            }

            foreach ($uow->getScheduledEntityDeletions() as $entity) {
                $strategy->audit($entity, [], IAuditStrategy::EVENT_ENTITY_DELETION, $ctx);
            }

            foreach ($uow->getScheduledCollectionUpdates() as $col) {
                $strategy->audit($col, [], IAuditStrategy::EVENT_COLLECTION_UPDATE, $ctx);
            }
        } catch (\Exception $e) {
            Log::error('Audit event listener failed', [
                'error' => $e->getMessage(),
                'strategy_class' => get_class($strategy),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get the appropriate audit strategy based on environment configuration
     */
    private function getAuditStrategy($em): ?IAuditStrategy
    {
        // Check if OTLP audit is enabled
        if (config('opentelemetry.enabled', false)) {
            try {
                Log::debug("AuditEventListener::getAuditStrategy strategy AuditLogOtlpStrategy");
                return App::make(AuditLogOtlpStrategy::class);
            } catch (\Exception $e) {
                Log::warning('Failed to create OTLP audit strategy, falling back to database', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Use database strategy (either as default or fallback)
        Log::debug("AuditEventListener::getAuditStrategy strategy AuditLogStrategy");
        return new AuditLogStrategy($em);
    }

    private function buildAuditContext(): AuditContext
    {
    
        if (app()->runningInConsole()) {
            Log::debug('AuditEventListener::buildAuditContext - running in console, attempting to get baggage context');
            $contextFromBaggage = $this->buildContextFromBaggage();
            if ($contextFromBaggage) {
                Log::debug('AuditEventListener::buildAuditContext - context successfully loaded from baggage');
                return $contextFromBaggage;
            }
            Log::debug('AuditEventListener::buildAuditContext - failed to load context from baggage, will use current request context');
        }

        /***
           * here we have 2 cases
           * 1. we are connecting to the IDP using an external APi ( under oauth2 ) so the
           * resource context have a client id and have a user id
           * 2. we are logged at idp and using the UI ( $user = Auth::user() )
       ***/

        $resource_server_context =  app(IResourceServerContext::class);
        $oauth2_current_client_id = $resource_server_context->getCurrentClientId();

        if(!empty($oauth2_current_client_id)) {
            $userId = $resource_server_context->getCurrentUserId();
            // here $userId can be null bc
            // $resource_server_context->getApplicationType() == IClient::ApplicationType_Service
            $user = $userId ? app(IUserRepository::class)->getById($userId) : null;
        }
        else{
            // 2. we are at IDP UI
            $user = Auth::user();
        }

        $defaultUiContext = [
            'app'  => null,
            'flow' => null
        ];

        $uiContext = [
            ...$defaultUiContext,
            // ...app()->bound('ui.context') ? app('ui.context') : [],
        ];

        $req = request();
        $rawRoute = null;
        // does not resolve the route when app is running in console mode
        if ($req instanceof Request && !app()->runningInConsole()) {
            try {
                $route = Route::getRoutes()->match($req);
                $method = $route->methods[0] ?? 'UNKNOWN';
                $rawRoute = $method . self::ROUTE_METHOD_SEPARATOR . $route->uri;
            } catch (\Exception $e) {
                Log::warning($e);
            }
        }

        return new AuditContext(
            userId: $user?->getId(),
            userEmail: $user?->getEmail(),
            userFirstName: $user?->getFirstName(),
            userLastName: $user?->getLastName(),
            uiApp: $uiContext['app'],
            uiFlow: $uiContext['flow'],
            route: $req?->path(),
            httpMethod: $req?->method(),
            clientIp: $req?->ip(),
            userAgent: $req?->userAgent(),
            rawRoute: $rawRoute
        );
    }

    /**
     * Rebuild audit context from OpenTelemetry Baggage (propagated from request to job)
     */
    private function buildContextFromBaggage(): ?AuditContext
    {
        try {
            $baggage = Baggage::getCurrent();
            
            Log::debug('AuditEventListener::buildContextFromBaggage - baggage obtained', [
                'baggage_class' => get_class($baggage),
            ]);
            
            $userIdEntry = $baggage->getEntry('audit.userId');
            Log::debug('AuditEventListener::buildContextFromBaggage - userId entry', [
                'entry_exists' => $userIdEntry !== null,
                'entry_class' => $userIdEntry ? get_class($userIdEntry) : 'null',
            ]);
            
            $userId = $userIdEntry ? $userIdEntry->getValue() : null;
            
            Log::debug('AuditEventListener::buildContextFromBaggage - userId value', [
                'userId' => $userId,
                'userId_type' => gettype($userId),
                'isEmpty' => empty($userId),
            ]);
            
            if (!$userId) {
                Log::debug('AuditEventListener: no userId in baggage');
                return null;
            }

            $userEmail = $baggage->getEntry('audit.userEmail')?->getValue();
            $userFirstName = $baggage->getEntry('audit.userFirstName')?->getValue();
            $userLastName = $baggage->getEntry('audit.userLastName')?->getValue();
            $route = $baggage->getEntry('audit.route')?->getValue();
            $httpMethod = $baggage->getEntry('audit.httpMethod')?->getValue();
            $clientIp = $baggage->getEntry('audit.clientIp')?->getValue();
            $userAgent = $baggage->getEntry('audit.userAgent')?->getValue();
            
            Log::debug('AuditEventListener::buildContextFromBaggage - extracted values', [
                'userId' => $userId,
                'userEmail' => $userEmail,
                'userFirstName' => $userFirstName,
                'userLastName' => $userLastName,
                'route' => $route,
                'httpMethod' => $httpMethod,
                'clientIp' => $clientIp,
                'userAgent' => $userAgent,
            ]);
            
            $auditContext = new AuditContext(
                userId: (int)$userId > 0 ? (int)$userId : null,
                userEmail: $userEmail,
                userFirstName: $userFirstName,
                userLastName: $userLastName,
                route: $route,
                httpMethod: $httpMethod,
                clientIp: $clientIp,
                userAgent: $userAgent,
            );
            
            Log::debug('AuditEventListener::buildContextFromBaggage - context created successfully');
            
            return $auditContext;
        } catch (\Exception $e) {
            Log::debug('AuditEventListener: could not build context from baggage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}