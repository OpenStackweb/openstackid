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

use App\Models\Utils\BaseEntity;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;

/**
 * Detaches every entity from the entity manager once a queued job is done.
 *
 * A queue worker is one long-lived process and the entity manager is shared by all the jobs it runs.
 * Nothing releases the entities a job loaded or persisted, so the unit of work keeps them (about 16 KB
 * per AddUserAction job, a User and a UserAction) until the worker hits the php memory limit and dies.
 */
class ClearEntityManagerAfterJobListener
{
    public function handleJobProcessed(JobProcessed $event): void
    {
        $this->clear($event->job);
    }

    public function handleJobFailed(JobFailed $event): void
    {
        $this->clear($event->job);
    }

    public function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        $this->clear($event->job);
    }

    private function clear(Job $job): void
    {
        // a sync job runs inside its caller (web request, console command): the caller keeps using its entities
        if ($job instanceof SyncJob) {
            return;
        }

        try {
            if (!Registry::getManager(BaseEntity::EntityManager)->isOpen()) {
                // closed by a failed transaction: the next job needs a usable one
                Registry::resetManager(BaseEntity::EntityManager);
                return;
            }
            Registry::getManager(BaseEntity::EntityManager)->clear();
        } catch (\Throwable $ex) {
            Log::warning('ClearEntityManagerAfterJobListener::clear failed', [
                'job' => $job->resolveName(),
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
