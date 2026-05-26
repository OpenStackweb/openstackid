<?php
namespace App\Services\Auth;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Jobs\EmitAuditLogJob;
use App\libs\Auth\Models\TwoFactorAuditLog;
use Auth\Repositories\ITwoFactorAuditLogRepository;
use Auth\User;
use Illuminate\Support\Facades\Log;

/**
 * Class TwoFactorAuditService
 * @package App\Services\Auth
 */
final class TwoFactorAuditService implements ITwoFactorAuditService
{
    public function __construct(
        private readonly ITwoFactorAuditLogRepository $repository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function log(User $user, string $eventType, string $method, string $ipAddress, ?array $metadata = null): void
    {
        Log::debug('TwoFactorAuditService::log', [
            'user_id' => $user->getId(),
            'event_type' => $eventType,
            'method' => $method,
            'ip_address' => $ipAddress,
        ]);

        $auditLog = new TwoFactorAuditLog();
        $auditLog->setUser($user);
        $auditLog->setEventType($eventType);   // throws InvalidArgumentException on unknown type
        $auditLog->setMethod($method);          // throws InvalidArgumentException on unknown method
        $auditLog->setIpAddress($ipAddress);
        // user_agent is captured from the current HTTP request context; falls back to empty
        // string in CLI / queue contexts. A future signature change may accept $userAgent
        // explicitly if project conventions require it (see ticket CU-86ba2z5gz).
        $auditLog->setUserAgent(request()?->userAgent() ?? '');
        $auditLog->setMetadata($metadata);

        $this->repository->add($auditLog, true);

        if (config('opentelemetry.enabled', false)) {
            EmitAuditLogJob::dispatch('two_factor.audit', [
                'two_factor.event_type' => $eventType,
                'two_factor.method' => $method,
                'two_factor.user_id' => $user->getId(),
                'two_factor.ip_address' => $ipAddress,
                'two_factor.success' => $this->resolveSuccess($eventType),
                'two_factor.device_trusted' => $eventType === TwoFactorAuditLog::EventDeviceTrusted,
                'elasticsearch.index' => config('opentelemetry.logs.elasticsearch_index', 'logs-audit'),
            ]);
        }
    }

    /**
     * Derive whether the 2FA event represents a successful outcome.
     * Only challenge_failed is treated as a failure; all other event types
     * represent informational or successful operations.
     */
    private function resolveSuccess(string $eventType): bool
    {
        return $eventType !== TwoFactorAuditLog::EventChallengeFailed;
    }
}
