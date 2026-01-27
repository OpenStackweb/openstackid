<?php

namespace Tests\OpenTelemetry;

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

use Tests\Jobs\TestAuditContextJob;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class JobContextIntegrationTest extends TestCase
{
    use CreatesApplication;

    private const QUEUE_NAME_DEFAULT = 'default';
    private const QUEUE_NAME_AUDIT = 'audit';
    private const EXPECTED_JOB_COUNT = 3;
    private const JOB_TIMEOUT_SECONDS = 300;
    private const JOB_MAX_TRIES = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApplication();
        $this->app['config']['opentelemetry.enabled'] = true;
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function testJobDispatchAttachesSerializedContext(): void
    {
        Queue::fake();
        TestAuditContextJob::dispatch('job-dispatch-test');

        Queue::assertPushed(TestAuditContextJob::class, fn($job) => is_array($job->payload()['data']));
    }

    public function testMultipleJobsCanQueueWithContext(): void
    {
        Queue::fake();

        for ($i = 0; $i < self::EXPECTED_JOB_COUNT; $i++) {
            TestAuditContextJob::dispatch("job-queue-test-$i");
        }

        $this->assertCount(self::EXPECTED_JOB_COUNT, Queue::pushed(TestAuditContextJob::class));
    }

    public function testJobPayloadStructure(): void
    {
        Queue::fake();
        TestAuditContextJob::dispatch('job-payload-test');

        Queue::assertPushed(TestAuditContextJob::class, function ($job) {
            $payload = $job->payload();
            
            $this->assertArrayHasKey('data', $payload);
            $this->assertArrayHasKey('displayName', $payload);
            $this->assertArrayHasKey('job', $payload);
            $this->assertArrayHasKey('maxTries', $payload);
            $this->assertArrayHasKey('timeout', $payload);
            $this->assertArrayHasKey('timeoutAt', $payload);
            
            return true;
        });
    }

    public function testNoContextIsAttachedWhenOTLPDisabled(): void
    {

        Queue::fake();
        TestAuditContextJob::dispatch('disabled-test');

        Queue::assertPushed(TestAuditContextJob::class, 
            fn($job) => !isset($job->payload()['data']['auditContext'])
        );
    }

    public function testQueueNameIsPreserved(): void
    {
        Queue::fake();
        TestAuditContextJob::dispatch('job-queue-name-test')->onQueue(self::QUEUE_NAME_AUDIT);

        Queue::assertPushed(TestAuditContextJob::class, fn($job) => $job->queue === self::QUEUE_NAME_AUDIT);
    }

    public function testJobTimeoutIsPreserved(): void
    {
        Queue::fake();
        TestAuditContextJob::dispatch('job-timeout-test')->timeout(self::JOB_TIMEOUT_SECONDS);

        Queue::assertPushed(TestAuditContextJob::class, fn($job) => $job->timeout === self::JOB_TIMEOUT_SECONDS);
    }

    public function testJobTriesIsPreserved(): void
    {
        Queue::fake();
        TestAuditContextJob::dispatch('job-tries-test')->tries(self::JOB_MAX_TRIES);

        Queue::assertPushed(TestAuditContextJob::class, fn($job) => $job->tries === self::JOB_MAX_TRIES);
    }
}
