<?php

namespace Tests\OpenTelemetry\Listeners;

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
use App\Listeners\RestoreJobAuditContextListener;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class RestoreJobAuditContextListenerTest extends TestCase
{
    use CreatesApplication;

    private const DEFAULT_QUEUE_NAME = 'default';
    
    private const TEST_USER_ID = 42;
    private const TEST_USER_EMAIL = 'test-user@example.com';
    private const TEST_USER_FIRST_NAME = 'Test';
    private const TEST_USER_LAST_NAME = 'User';
    private const TEST_ROUTE = '/api/v2/test-endpoint';
    private const TEST_HTTP_METHOD = 'POST';
    private const TEST_CLIENT_IP = '127.0.0.1';
    private const TEST_USER_AGENT = 'PHPUnit-Test/1.0';

    protected RestoreJobAuditContextListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApplication();
        $this->listener = new RestoreJobAuditContextListener();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    /**
     * Creates a valid test AuditContext with meaningful test data.
     */
    private function createTestAuditContext(): AuditContext
    {
        return new AuditContext(
            userId: self::TEST_USER_ID,
            userEmail: self::TEST_USER_EMAIL,
            userFirstName: self::TEST_USER_FIRST_NAME,
            userLastName: self::TEST_USER_LAST_NAME,
            route: self::TEST_ROUTE,
            httpMethod: self::TEST_HTTP_METHOD,
            clientIp: self::TEST_CLIENT_IP,
            userAgent: self::TEST_USER_AGENT,
        );
    }

    /**
     * Creates a job payload structure with serialized context.
     */
    private function createPayloadWithContext(AuditContext $context): array
    {
        return [
            'uuid' => 'test-uuid-' . uniqid(),
            'displayName' => TestAuditContextJob::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => 1,
            'timeout' => 60,
            'timeoutAt' => null,
            'data' => [
                'auditContext' => serialize($context),
            ],
        ];
    }

    /**
     * Creates a minimal job payload without context data.
     */
    private function createPayloadWithoutContext(): array
    {
        return [
            'uuid' => 'test-uuid-' . uniqid(),
            'displayName' => TestAuditContextJob::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [],
        ];
    }

    private function createMockJobWithPayload(array $payload): \Illuminate\Contracts\Queue\Job
    {
        $mockJob = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $mockJob->method('payload')->willReturn($payload);

        return $mockJob;
    }

    public function testListenerRestoresContextFromPayload(): void
    {
        // Arrange: Enable OTLP and create a valid context
        $this->app['config']['opentelemetry.enabled'] = true;
        $context = $this->createTestAuditContext();
        $payload = $this->createPayloadWithContext($context);

        // Act
        $mockJob = $this->createMockJobWithPayload($payload);
        $event = new JobProcessing(self::DEFAULT_QUEUE_NAME, $mockJob);
        $this->listener->handle($event);

        // Assert: Context was bound to container
        $this->assertTrue(App::bound(AuditContext::CONTAINER_KEY));
        $restoredContext = App::make(AuditContext::CONTAINER_KEY);
        
        $this->assertInstanceOf(AuditContext::class, $restoredContext);
        $this->assertEquals(self::TEST_USER_ID, $restoredContext->userId);
        $this->assertEquals(self::TEST_USER_EMAIL, $restoredContext->userEmail);
    }

    public function testListenerDoesNotBindMissingContext(): void
    {
        // Arrange: Enable OTLP but provide payload without context
        $this->app['config']['opentelemetry.enabled'] = true;
        $payload = $this->createPayloadWithoutContext();

        // Act
        $mockJob = $this->createMockJobWithPayload($payload);
        $event = new JobProcessing(self::DEFAULT_QUEUE_NAME, $mockJob);
        $this->listener->handle($event);

        // Assert: No context binding occurs
        $this->assertFalse(App::bound(AuditContext::CONTAINER_KEY));
    }

    public function testListenerSkipsWhenOTLPDisabled(): void
    {
        // Arrange: Disable OTLP
        $this->app['config']['opentelemetry.enabled'] = false;
        $context = $this->createTestAuditContext();
        $payload = $this->createPayloadWithContext($context);

        // Act
        $mockJob = $this->createMockJobWithPayload($payload);
        $event = new JobProcessing(self::DEFAULT_QUEUE_NAME, $mockJob);
        $this->listener->handle($event);

        // Assert: No context binding even though context exists
        $this->assertFalse(App::bound(AuditContext::CONTAINER_KEY));
    }

    public function testListenerHandlesInvalidSerializedData(): void
    {
        // Arrange: Enable OTLP with invalid serialized data
        $this->app['config']['opentelemetry.enabled'] = true;
        $payload = [
            'uuid' => 'test-uuid-' . uniqid(),
            'data' => ['auditContext' => 'not-serialized-data'],
        ];

        // Act
        $mockJob = $this->createMockJobWithPayload($payload);
        $event = new JobProcessing(self::DEFAULT_QUEUE_NAME, $mockJob);
        $this->listener->handle($event);

        // Assert: Error is handled gracefully, no context bound
        $this->assertFalse(App::bound(AuditContext::CONTAINER_KEY));
    }

    public function testListenerHandlesPayloadRetrievalException(): void
    {
        // Arrange: Create mock job that throws exception on payload access
        $this->app['config']['opentelemetry.enabled'] = true;
        $mockJob = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $mockJob->method('payload')
            ->willThrowException(new \RuntimeException('Payload retrieval failed'));

        // Act & Assert: Exception is caught and logged, no context bound
        $event = new JobProcessing(self::DEFAULT_QUEUE_NAME, $mockJob);
        $this->listener->handle($event);
        
        $this->assertFalse(App::bound(AuditContext::CONTAINER_KEY));
    }
}
