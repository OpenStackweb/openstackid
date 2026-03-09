<?php namespace App\Jobs;
/*
 * Copyright 2024 OpenStack Foundation
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

use Auth\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OAuth2\Services\ITokenService;
use Utils\IPHelper;

/**
 * Class RevokeUserGrants
 * @package App\Jobs
 */
class RevokeUserGrants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $timeout = 0;

    /**
     * @var int
     */
    private int $user_id;

    /**
     * @var string|null
     */
    private ?string $client_id;

    /**
     * @var string
     */
    private string $reason;

    /**
     * @param User $user
     * @param string|null $client_id  null = revoke across all clients
     * @param string $reason          audit message suffix
     */
    public function __construct(User $user, ?string $client_id = null, string $reason = 'explicit logout')
    {
        $this->user_id   = $user->getId();
        $this->client_id = $client_id;
        $this->reason    = $reason;
        Log::debug(sprintf(
            "RevokeUserGrants::constructor user %s client_id %s reason %s",
            $this->user_id,
            $client_id ?? 'N/A',
            $reason
        ));
    }

    public function handle(ITokenService $service): void
    {
        Log::debug("RevokeUserGrants::handle");

        try {
            $scope = !empty($this->client_id)
                ? sprintf("client %s", $this->client_id)
                : "all clients";

            $action = sprintf(
                "Revoking all grants for user %s on %s due to %s.",
                $this->user_id,
                $scope,
                $this->reason
            );

            AddUserAction::dispatch($this->user_id, IPHelper::getUserIp(), $action);

            // Emit to OTEL audit log (Elasticsearch) if enabled
            if (config('opentelemetry.enabled', false)) {
                EmitAuditLogJob::dispatch('audit.security.tokens_revoked', [
                    'audit.action'      => 'revoke_tokens',
                    'audit.entity'      => 'User',
                    'audit.entity_id'   => (string) $this->user_id,
                    'audit.timestamp'   => now()->toISOString(),
                    'audit.description' => $action,
                    'audit.reason'      => $this->reason,
                    'audit.scope'       => $scope,
                    'auth.user.id'      => $this->user_id,
                    'client.ip'         => IPHelper::getUserIp(),
                    'elasticsearch.index' => config('opentelemetry.logs.elasticsearch_index', 'logs-audit'),
                ]);
            }

            $service->revokeUsersToken($this->user_id, $this->client_id);
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(sprintf("RevokeUserGrants::failed %s", $exception->getMessage()));
    }
}
