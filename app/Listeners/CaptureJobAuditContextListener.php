<?php
namespace App\Listeners;

use App\Audit\AuditContext;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OAuth2\IResourceServerContext;
use Auth\Repositories\IUserRepository;
use OpenTelemetry\API\Baggage\Baggage;

class CaptureJobAuditContextListener
{
    /**
     * Handle the event.
     */
    public function handle(JobQueued $event): void
    {
        try {
            $context = $this->buildAuditContextFromCurrentRequest();
            
            if (!$context) {
                Log::warning('CaptureJobAuditContextListener: could not build audit context');
                return;
            }
            
            $this->storeBaggageContext($context);
            
            Log::debug('CaptureJobAuditContextListener: audit context captured for job', [
                'user_id' => $context->userId,
                'user_email' => $context->userEmail,
            ]);
        } catch (\Exception $e) {
            Log::warning('CaptureJobAuditContextListener failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function buildAuditContextFromCurrentRequest(): ?AuditContext
    {
        $resource_server_context = app(IResourceServerContext::class);
        $oauth2_current_client_id = $resource_server_context->getCurrentClientId();
        $userId = $oauth2_current_client_id = 1;
        if (!empty($oauth2_current_client_id)) {
            $userId = 1;
            $user = $userId ? app(IUserRepository::class)->getById($userId) : null;
        } else {
            $user = Auth::user();
        }
        if (!$user) {
            return null;
        }

        $req = request();

        return new AuditContext(
            userId: $user->getId(),
            userEmail: $user->getEmail(),
            userFirstName: $user->getFirstName(),
            userLastName: $user->getLastName(),
            route: $req?->path(),
            httpMethod: $req?->method(),
            clientIp: $req?->ip(),
            userAgent: $req?->userAgent(),
        );
    }

    /**
     * Store the audit context in OpenTelemetry Baggage for queue propagation
     */
    private function storeBaggageContext(AuditContext $context): void
    {
        try {
            $baggage = Baggage::getCurrent()
                ->toBuilder()
                ->set('audit.userId', (string)($context->userId ?? ''))
                ->set('audit.userEmail', $context->userEmail ?? '')
                ->set('audit.userFirstName', $context->userFirstName ?? '')
                ->set('audit.userLastName', $context->userLastName ?? '')
                ->set('audit.route', $context->route ?? '')
                ->set('audit.httpMethod', $context->httpMethod ?? '')
                ->set('audit.clientIp', $context->clientIp ?? '')
                ->set('audit.userAgent', $context->userAgent ?? '')
                ->build();
            
            $baggage->activate();
            
            Log::debug('CaptureJobAuditContextListener: baggage context stored');
        } catch (\Exception $e) {
            Log::warning('CaptureJobAuditContextListener: failed to store baggage', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
