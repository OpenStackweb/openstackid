<?php

namespace Tests\Worker;

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
use App\Worker\DoctrineWorker;
use Auth\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Log;
use LaravelDoctrine\ORM\Facades\Registry;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

/**
 * Command run by the fake job: records what the entity manager looks like at the moment the job runs.
 */
final class RecordingQueueJob
{
    public static int $runs = 0;
    public static ?int $managedEntitiesWhenRun = null;
    public static ?bool $entityManagerOpenWhenRun = null;

    public function __construct(public string $secret = '')
    {
    }

    public function handle(): void
    {
        self::$runs++;
        $em = Registry::getManager(BaseEntity::EntityManager);
        self::$entityManagerOpenWhenRun = $em->isOpen();
        self::$managedEntitiesWhenRun = array_sum(array_map('count', $em->getUnitOfWork()->getIdentityMap()));
    }
}

/**
 * A queue worker is one long-lived process and all its jobs share one entity manager: DoctrineWorker has to
 * leave it open and empty before every job, otherwise the worker grows until it hits the php memory limit.
 */
class DoctrineWorkerTest extends TestCase
{
    use CreatesApplication;

    private const USER_ID = 987654321;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApplication();
        RecordingQueueJob::$runs = 0;
        RecordingQueueJob::$managedEntitiesWhenRun = null;
        RecordingQueueJob::$entityManagerOpenWhenRun = null;
    }

    protected function tearDown(): void
    {
        Registry::resetManager(BaseEntity::EntityManager);
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    private function makeJob(string $secret = ''): SyncJob
    {
        $payload = json_encode([
            'displayName' => RecordingQueueJob::class,
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'maxTries' => null,
            'timeout' => null,
            'data' => [
                'commandName' => RecordingQueueJob::class,
                'command' => serialize(new RecordingQueueJob($secret)),
            ],
        ]);

        return new SyncJob($this->app, $payload, 'redis', 'default');
    }

    private function runJob(DoctrineWorker $worker, SyncJob $job): void
    {
        (new \ReflectionMethod($worker, 'runJob'))->invoke($worker, $job, 'redis', new WorkerOptions());
    }

    /**
     * Registers one entity in the identity map of the shared entity manager, without a query.
     */
    private function manageAnEntity(): void
    {
        Registry::getManager(BaseEntity::EntityManager)->getReference(User::class, self::USER_ID);
    }

    public function testEmptiesTheUnitOfWorkBeforeTheJobRuns(): void
    {
        $this->manageAnEntity();

        $this->runJob($this->app->make(DoctrineWorker::class), $this->makeJob());

        $this->assertSame(1, RecordingQueueJob::$runs);
        $this->assertSame(0, RecordingQueueJob::$managedEntitiesWhenRun);
    }

    public function testOpensANewEntityManagerWhenAFailedTransactionClosedIt(): void
    {
        Registry::getManager(BaseEntity::EntityManager)->close();

        $this->runJob($this->app->make(DoctrineWorker::class), $this->makeJob());

        $this->assertSame(1, RecordingQueueJob::$runs);
        $this->assertTrue(RecordingQueueJob::$entityManagerOpenWhenRun);
    }

    public function testPutsTheJobBackAndStopsTheWorkerWhenTheSetupFails(): void
    {
        $worker = new class(
            $this->app['queue'],
            $this->app['events'],
            $this->app[ExceptionHandler::class],
            fn(): bool => false
        ) extends DoctrineWorker {
            protected function prepareEntityManager(): void
            {
                throw new \RuntimeException('entity manager is not usable');
            }
        };
        $job = $this->makeJob();

        $this->runJob($worker, $job);

        $this->assertSame(0, RecordingQueueJob::$runs, 'the job must not run on a broken entity manager');
        $this->assertTrue($job->isReleased(), 'the job must go back to the queue');
        $this->assertTrue($worker->shouldQuit, 'the worker must stop so it is restarted clean');
    }

    public function testDoesNotWriteThePayloadOfTheJobInTheLogs(): void
    {
        Log::spy();

        $this->runJob($this->app->make(DoctrineWorker::class), $this->makeJob('one-time-code-424242'));

        Log::shouldHaveReceived('debug')->withArgs(fn(string $message) => str_contains($message, 'DoctrineWorker::runJob'))->once();
        Log::shouldNotHaveReceived('debug', [\Mockery::on(fn($message) => is_string($message) && str_contains($message, 'one-time-code-424242'))]);
        // the Mockery expectations above are not counted as assertions by PHPUnit
        $this->addToAssertionCount(2);
    }
}
