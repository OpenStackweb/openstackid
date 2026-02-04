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
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Support\Facades\Log;

/**
 * Cleans up audit context after job processing to prevent context leakage between jobs
 */
class CleanupJobAuditContextListener
{
    public function handleJobProcessed(JobProcessed $event): void
    {
        $this->cleanup(get_class($event->job));
    }

    public function handleJobFailed(JobFailed $event): void
    {
        $this->cleanup(get_class($event->job));
    }

    public function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        $this->cleanup(get_class($event->job));
    }

    private function cleanup(string $jobClass): void
    {
        if (!config('opentelemetry.enabled', false)) {
            return;
        }

        try {
            if (app()->bound(AuditContext::CONTAINER_KEY)) {
                app()->forgetInstance(AuditContext::CONTAINER_KEY);
                Log::debug('CleanupJobAuditContextListener::cleanup audit context cleaned after job', [
                    'job' => $jobClass,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('CleanupJobAuditContextListener::cleanup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
