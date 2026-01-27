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

use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\Auth;
use OAuth2\IResourceServerContext;

class AuditContext
{
    public function __construct(
        public ?int $userId = null,
        public ?string $userEmail = null,
        public ?string $userFirstName = null,
        public ?string $userLastName = null,
        public ?string $uiApp = null,
        public ?string $uiFlow = null,
        public ?string $route = null,
        public ?string $rawRoute = null,
        public ?string $httpMethod = null,
        public ?string $clientIp = null,
        public ?string $userAgent = null,
    ) {
    }

    /**
     * Get the currently authenticated user from either OAuth2 or UI context
     * 
     * @return \Auth\User|null
     */
    public static function getCurrentUser()
    {
        $resourceContext = app(IResourceServerContext::class);
        $clientId = $resourceContext->getCurrentClientId();
        $userId = $resourceContext->getCurrentUserId();
        
        if (!empty($clientId) && $userId) {
            // OAuth2 context: user authenticated via API
            return app(IUserRepository::class)->getById($userId);
        }
        
        // UI context: user logged in at IDP
        return Auth::user();
    }

    /**
     * Create an AuditContext from the current request
     * Handles both OAuth2 and UI authentication contexts
     */
    public static function fromCurrentRequest(): ?self
    {
        try {
            $user = self::getCurrentUser();
            
            if (!$user) {
                return null;
            }
            
            $req = request();
            
            return new self(
                userId: $user->getId(),
                userEmail: $user->getEmail(),
                userFirstName: $user->getFirstName(),
                userLastName: $user->getLastName(),
                route: $req?->path(),
                httpMethod: $req?->method(),
                clientIp: $req?->ip(),
                userAgent: $req?->userAgent(),
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to build audit context from request', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}