<?php

namespace App\Worker;

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
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Worker as IlluminateWorker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;
use Throwable;

/**
 * A queue worker that is safe to use with Doctrine.
 *
 * A worker is one long-lived process and all its jobs share the same entity manager. Left alone, the unit of
 * work keeps every entity a job loaded or persisted until the process hits the php memory limit and dies.
 * Before each job this worker makes sure the entity manager is open, the connection is alive and the unit of
 * work is empty. Same approach as the DoctrineWorker of summit-api.
 */
class DoctrineWorker extends IlluminateWorker
{
    /**
     * @param $job
     * @param $connectionName
     * @param WorkerOptions $options
     */
    protected function runJob($job, $connectionName, WorkerOptions $options)
    {
        try {
            // only the job name: the payload of a job can carry personal data (emails, one time codes)
            Log::debug(sprintf("DoctrineWorker::runJob %s connectionName %s", $job->resolveName(), $connectionName));
            $this->prepareEntityManager();
        } catch (Throwable $exception) {
            Log::error(sprintf("DoctrineWorker::runJob setup error %s %s", get_class($exception), $exception->getMessage()));
            // the parent runJob reports the exceptions of the job itself: anything caught here comes from the setup
            $this->exceptions->report($exception);
            $this->requeueJob($job);
            $this->signalWorkerProcessShouldStop();
            return;
        }

        return parent::runJob($job, $connectionName, $options);
    }

    /**
     * Leaves the entity manager open, connected and without managed entities.
     */
    protected function prepareEntityManager(): void
    {
        $em = $this->getEntityManager();
        $this->ensureDatabaseConnectionIsOpen($em->getConnection());
        // nothing must persist between two jobs
        $em->clear();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        $em = Registry::getManager(BaseEntity::EntityManager);

        if (!$em->isOpen()) {
            Log::warning("DoctrineWorker::getEntityManager entity manager is closed, opening a new one");
            $em = Registry::resetManager(BaseEntity::EntityManager);
        }

        return $em;
    }

    /**
     * mysql closes an idle connection after wait_timeout and a worker is meant to run for days: ping it
     * before each job and reconnect, otherwise the next query fails with "MySQL server has gone away".
     */
    private function ensureDatabaseConnectionIsOpen(Connection $con): void
    {
        try {
            $con->executeQuery($con->getDatabasePlatform()->getDummySelectSQL());
        } catch (Throwable $ex) {
            Log::warning(sprintf("DoctrineWorker::ensureDatabaseConnectionIsOpen ping failed %s, reconnecting", $ex->getMessage()));
            $con->close();
            $con->connect();
        }
    }

    /**
     * Puts the job back on the queue right away so another worker (or this one after it restarts) takes it:
     * if we are here the job never ran, so its backoff does not apply.
     */
    private function requeueJob(Job $job): void
    {
        if (!$job->isDeleted() && !$job->isReleased() && !$job->hasFailed()) {
            $job->release();
        }
    }

    /**
     * Ends the worker so the process supervisor starts a clean one.
     */
    private function signalWorkerProcessShouldStop(): void
    {
        $this->shouldQuit = true;
    }
}
