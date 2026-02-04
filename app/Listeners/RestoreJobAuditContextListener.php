<?php
namespace App\Listeners;

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

use App\Audit\AuditContext;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;

class RestoreJobAuditContextListener
{
    private const PAYLOAD_DATA_KEY = 'data';
    private const PAYLOAD_CONTEXT_KEY = 'auditContext';
    private const LOG_CONTEXT_KEY = 'event_name';
    private const LOG_CONTEXT_VALUE = 'job.processing';

    public function handle(JobProcessing $event): void
    {
        if (!$this->isOpenTelemetryEnabled()) {
            return;
        }

        try {
            $context = $this->extractContextFromPayload($event->job->payload());
            
            if ($context !== null) {
                app()->instance(AuditContext::CONTAINER_KEY, $context);
            }
        } catch (\Exception $e) {
            Log::warning('RestoreJobAuditContextListener::handle Failed to restore audit context from queue job', [
                self::LOG_CONTEXT_KEY => self::LOG_CONTEXT_VALUE,
                'exception_message' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
        }
    }

    private function isOpenTelemetryEnabled(): bool
    {
        return config('opentelemetry.enabled', false);
    }

    private function extractContextFromPayload(array $payload): ?AuditContext
    {
        if (!isset($payload[self::PAYLOAD_DATA_KEY][self::PAYLOAD_CONTEXT_KEY])) {
            return null;
        }

        try {
            $context = unserialize(
                $payload[self::PAYLOAD_DATA_KEY][self::PAYLOAD_CONTEXT_KEY],
                ['allowed_classes' => [AuditContext::class]]
            );

            if (!$context instanceof AuditContext) {
                Log::warning('RestoreJobAuditContextListener::extractContextFromPayload Invalid audit context type in job payload', [
                    self::LOG_CONTEXT_KEY => self::LOG_CONTEXT_VALUE,
                    'actual_type' => gettype($context),
                ]);
                return null;
            }

            return $context;
        } catch (\Exception $e) {
            Log::warning('RestoreJobAuditContextListener::extractContextFromPayload Failed to unserialize audit context from job payload', [
                self::LOG_CONTEXT_KEY => self::LOG_CONTEXT_VALUE,
                'exception_message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
