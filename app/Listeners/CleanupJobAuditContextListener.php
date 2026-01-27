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

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Log;

/**
 * Cleans up audit context after job processing to prevent context leakage between jobs
 */
class CleanupJobAuditContextListener
{
    public function handle(JobProcessed $event): void
    {
        if (!config('opentelemetry.enabled', false)) {
            return;
        }

        try {
            if (app()->bound('audit.context')) {
                app()->forgetInstance('audit.context');
                Log::debug('CleanupJobAuditContextListener: audit context cleaned after job', [
                    'job' => get_class($event->job),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('CleanupJobAuditContextListener failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
