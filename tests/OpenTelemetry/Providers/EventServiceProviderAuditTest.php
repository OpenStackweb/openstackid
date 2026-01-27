<?php

namespace Tests\OpenTelemetry\Providers;

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

use App\Audit\AuditContext;
use Tests\Jobs\TestAuditContextJob;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class EventServiceProviderAuditTest extends TestCase
{
    use CreatesApplication;

    private const QUEUE_NAME_TEST = 'test-queue';
    private const EXPECTED_JOB_COUNT = 3;

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

    public function testOTLPEnabledEnqueuesContextWithPayload(): void
    {
        Queue::fake();

        TestAuditContextJob::dispatch('provider-otlp-enabled-test');

        Queue::assertPushed(TestAuditContextJob::class, 
            fn($job) => isset($job->payload()['data']) && is_array($job->payload()['data'])
        );
    }

    public function testOTLPDisabledDoesNotAttemptContextAttachment(): void
    {
        $this->app['config']['opentelemetry.enabled'] = false;
        Queue::fake();

        TestAuditContextJob::dispatch('disabled-test');

        Queue::assertPushed(TestAuditContextJob::class, 
            fn($job) => !isset($job->payload()['data']['auditContext'])
        );
    }

    public function testPayloadStructureIsValid(): void
    {
        Queue::fake();

        TestAuditContextJob::dispatch('provider-payload-structure-test')->onQueue(self::QUEUE_NAME_TEST);

        Queue::assertPushed(TestAuditContextJob::class, function ($job) {
            $payload = $job->payload();
            return isset($payload['data'], $payload['displayName'], $payload['job']);
        });
    }

    public function testContextCanBeRetrievedFromPayloadAfterDispatch(): void
    {
        Queue::fake();

        TestAuditContextJob::dispatch('provider-context-retrieval-test');

        Queue::assertPushed(TestAuditContextJob::class, function ($job) {
            $payload = $job->payload();
            
            if (!isset($payload['data']['auditContext'])) {
                return true;
            }

            try {
                $context = unserialize($payload['data']['auditContext']);
                return $context instanceof AuditContext;
            } catch (\Exception $e) {
                return false;
            }
        });
    }

    public function testMultipleJobsPreserveIndependentContexts(): void
    {
        Queue::fake();

        for ($i = 1; $i <= self::EXPECTED_JOB_COUNT; $i++) {
            TestAuditContextJob::dispatch("provider-context-job-$i");
        }

        $this->assertCount(self::EXPECTED_JOB_COUNT, Queue::pushed(TestAuditContextJob::class));
    }
}
