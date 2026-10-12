<?php

namespace Tests\Listeners;

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
use Auth\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Jobs\SyncJob;
use LaravelDoctrine\ORM\Facades\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

/**
 * A queue worker is one long-lived process: the entities a job loads must not outlive it,
 * otherwise the worker grows until it hits the php memory limit.
 */
class ClearEntityManagerAfterJobListenerTest extends TestCase
{
    use CreatesApplication;

    private const USER_ID = 987654321;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApplication();
    }

    protected function tearDown(): void
    {
        Registry::resetManager(BaseEntity::EntityManager);
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    /**
     * Registers one entity in the identity map of the shared entity manager, without a query.
     */
    private function manageAnEntity(): void
    {
        Registry::getManager(BaseEntity::EntityManager)->getReference(User::class, self::USER_ID);
    }

    private function managedEntities(): int
    {
        $identityMap = Registry::getManager(BaseEntity::EntityManager)->getUnitOfWork()->getIdentityMap();
        return array_sum(array_map('count', $identityMap));
    }

    public static function queueEvents(): array
    {
        return [
            'job processed' => [fn(Job $job) => new JobProcessed('redis', $job)],
            'job failed' => [fn(Job $job) => new JobFailed('redis', $job, new \RuntimeException('boom'))],
            'job threw' => [fn(Job $job) => new JobExceptionOccurred('redis', $job, new \RuntimeException('boom'))],
        ];
    }

    #[DataProvider('queueEvents')]
    public function testDetachesEveryEntityOnceAQueuedJobIsDone(callable $event): void
    {
        $this->manageAnEntity();
        $this->assertSame(1, $this->managedEntities());

        $this->app['events']->dispatch($event($this->createMock(Job::class)));

        $this->assertSame(0, $this->managedEntities());
    }

    public function testKeepsTheEntitiesOfTheCallerWhenTheJobRunsSync(): void
    {
        $this->manageAnEntity();

        $this->app['events']->dispatch(
            new JobProcessed('sync', new SyncJob($this->app, '{}', 'sync', 'default'))
        );

        $this->assertSame(1, $this->managedEntities());
    }

    public function testReplacesAnEntityManagerClosedByAFailedTransaction(): void
    {
        Registry::getManager(BaseEntity::EntityManager)->close();

        $this->app['events']->dispatch(new JobFailed('redis', $this->createMock(Job::class), new \RuntimeException('boom')));

        $this->assertTrue(Registry::getManager(BaseEntity::EntityManager)->isOpen());
    }
}
