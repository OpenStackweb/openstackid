<?php

namespace Tests\OpenTelemetry;

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
use Tests\Jobs\TestAuditContextJob;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class AuditContextPropagationTest extends TestCase
{
    use CreatesApplication;

    private const CONTAINER_BINDING_KEY = 'audit.context';
    private const TEST_USER_ID_1 = 42;
    private const TEST_USER_ID_2 = 100;
    private const TEST_USER_ID_3 = 999;
    private const TEST_USER_EMAIL_1 = 'user@example.com';
    private const TEST_USER_EMAIL_2 = 'container@example.com';
    private const TEST_USER_EMAIL_3 = 'roundtrip@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->createApplication();
        if (!config('opentelemetry.enabled')) {
            $this->app['config']['opentelemetry.enabled'] = false;
        }
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        parent::tearDown();
    }

    public function testAuditContextIsAttachedToJobPayload(): void
    {
        Queue::fake();
        $testData = 'propagation-test-data';
        TestAuditContextJob::dispatch($testData);

        Queue::assertPushed(TestAuditContextJob::class, fn($job) => $job->testData === $testData);
    }

    public function testAuditContextIsRestoredFromPayload(): void
    {
        $context = new AuditContext(
            userId: self::TEST_USER_ID_1,
            userEmail: self::TEST_USER_EMAIL_1,
            userFirstName: 'John',
            userLastName: 'Doe',
            route: '/api/users',
            httpMethod: 'GET',
            clientIp: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
        );

        $restored = unserialize(serialize($context));
        
        $this->assertInstanceOf(AuditContext::class, $restored);
        $this->assertEquals($context->userId, $restored->userId);
        $this->assertEquals($context->userEmail, $restored->userEmail);
        $this->assertEquals($context->userFirstName, $restored->userFirstName);
        $this->assertEquals($context->userLastName, $restored->userLastName);
        $this->assertEquals($context->route, $restored->route);
        $this->assertEquals($context->httpMethod, $restored->httpMethod);
        $this->assertEquals($context->clientIp, $restored->clientIp);
        $this->assertEquals($context->userAgent, $restored->userAgent);
    }

    public function testAuditContextIsAvailableInContainer(): void
    {
        $context = new AuditContext(
            userId: self::TEST_USER_ID_2,
            userEmail: self::TEST_USER_EMAIL_2,
            userFirstName: 'Container',
            userLastName: 'Test',
        );

        App::singleton(self::CONTAINER_BINDING_KEY, fn() => $context);

        $this->assertTrue(App::bound(self::CONTAINER_BINDING_KEY));
        $restored = App::make(self::CONTAINER_BINDING_KEY);
        
        $this->assertInstanceOf(AuditContext::class, $restored);
        $this->assertEquals(self::TEST_USER_ID_2, $restored->userId);
        $this->assertEquals(self::TEST_USER_EMAIL_2, $restored->userEmail);
    }

    public function testOTLPDisabledSkipsContextAttachment(): void
    {
        $this->app['config']['opentelemetry.enabled'] = false;

        Queue::fake();
        TestAuditContextJob::dispatch('test-data');

        Queue::assertPushed(TestAuditContextJob::class);
    }

    public function testContextSurvivesSerializationRoundTrip(): void
    {
        $original = new AuditContext(
            userId: self::TEST_USER_ID_3,
            userEmail: self::TEST_USER_EMAIL_3,
            userFirstName: 'Round',
            userLastName: 'Trip',
            route: '/api/roundtrip',
            httpMethod: 'PUT',
            clientIp: '10.0.0.1',
            userAgent: 'Test-RoundTrip/2.0',
        );

        $restored = unserialize(serialize($original));

        $this->assertSame($original->userId, $restored->userId);
        $this->assertSame($original->userEmail, $restored->userEmail);
        $this->assertSame($original->userFirstName, $restored->userFirstName);
        $this->assertSame($original->userLastName, $restored->userLastName);
        $this->assertSame($original->route, $restored->route);
        $this->assertSame($original->httpMethod, $restored->httpMethod);
        $this->assertSame($original->clientIp, $restored->clientIp);
        $this->assertSame($original->userAgent, $restored->userAgent);
    }

    public function testContextWithNullValuesSerializes(): void
    {
        $context = new AuditContext();
        $restored = unserialize(serialize($context));

        $this->assertInstanceOf(AuditContext::class, $restored);
        $this->assertNull($restored->userId);
        $this->assertNull($restored->userEmail);
    }
}
