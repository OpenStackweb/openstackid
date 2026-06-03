<?php
namespace Tests\Unit;
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

use App\Jobs\EmitAuditLogJob;
use App\libs\Auth\Models\TwoFactorAuditLog;
use App\Services\Auth\TwoFactorAuditService;
use Auth\Repositories\ITwoFactorAuditLogRepository;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;
use Utils\Db\ITransactionService;

/**
 * Class TwoFactorAuditServiceTest
 * @package Tests\Unit
 */
final class TwoFactorAuditServiceTest extends TestCase
{
    /** @var TwoFactorAuditService */
    private TwoFactorAuditService $service;

    /** @var \Mockery\MockInterface&ITwoFactorAuditLogRepository */
    private $repository;

    /** @var \Mockery\MockInterface&ITransactionService */
    private $tx_service;

    /** @var \Mockery\MockInterface&User */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->repository = Mockery::mock(ITwoFactorAuditLogRepository::class);
        $this->tx_service = Mockery::mock(ITransactionService::class);
        $this->service = new TwoFactorAuditService($this->repository, $this->tx_service);

        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getId')->andReturn(42);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // log() persists TwoFactorAuditLog with correct fields
    // -------------------------------------------------------------------------

    public function testLogPersistsTwoFactorAuditLogWithCorrectFields(): void
    {
        /** @var TwoFactorAuditLog|null $persisted */
        $persisted = null;

        $this->repository
            ->shouldReceive('add')
            ->once()
            ->withArgs(function (TwoFactorAuditLog $log, bool $sync) use (&$persisted) {
                $persisted = $log;
                return $sync === false;
            });

        $this->tx_service
            ->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback();
            });

        $this->service->log(
            $this->user,
            TwoFactorAuditLog::EventChallengeSucceeded,
            TwoFactorAuditLog::MethodEmailOtp,
            '127.0.0.1',
            ['attempt' => 1]
        );

        $this->assertNotNull($persisted);
        $this->assertSame($this->user, $persisted->getUser());
        $this->assertSame(TwoFactorAuditLog::EventChallengeSucceeded, $persisted->getEventType());
        $this->assertSame(TwoFactorAuditLog::MethodEmailOtp, $persisted->getMethod());
        $this->assertSame('127.0.0.1', $persisted->getIpAddress());
        $this->assertSame(['attempt' => 1], $persisted->getMetadata());
    }

    // -------------------------------------------------------------------------
    // log() emits OTLP attributes
    // -------------------------------------------------------------------------

    public function testLogEmitsOtlpAttributes(): void
    {
        Config::set('opentelemetry.enabled', true);

        $this->repository->shouldReceive('add')->once();

        $this->tx_service
            ->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback();
            });

        $this->service->log(
            $this->user,
            TwoFactorAuditLog::EventChallengeSucceeded,
            TwoFactorAuditLog::MethodEmailOtp,
            '10.0.0.1'
        );

        Queue::assertPushed(EmitAuditLogJob::class, function (EmitAuditLogJob $job) {
            return $job->logMessage === 'two_factor.audit'
                && $job->auditData['two_factor.event_type'] === TwoFactorAuditLog::EventChallengeSucceeded
                && $job->auditData['two_factor.method'] === TwoFactorAuditLog::MethodEmailOtp
                && $job->auditData['two_factor.user_id'] === 42
                && $job->auditData['two_factor.ip_address'] === '10.0.0.1'
                && $job->auditData['two_factor.success'] === true
                && $job->auditData['two_factor.device_trusted'] === false;
        });
    }

    // -------------------------------------------------------------------------
    // log() emits two_factor.success = false for challenge_failed
    // -------------------------------------------------------------------------

    public function testLogEmitsSuccessFalseForChallengeFailed(): void
    {
        Config::set('opentelemetry.enabled', true);

        $this->repository->shouldReceive('add')->once();

        $this->tx_service
            ->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback();
            });

        $this->service->log(
            $this->user,
            TwoFactorAuditLog::EventChallengeFailed,
            TwoFactorAuditLog::MethodEmailOtp,
            '10.0.0.1'
        );

        Queue::assertPushed(EmitAuditLogJob::class, function (EmitAuditLogJob $job) {
            return $job->auditData['two_factor.event_type'] === TwoFactorAuditLog::EventChallengeFailed
                && $job->auditData['two_factor.success'] === false;
        });
    }

    // -------------------------------------------------------------------------
    // log() does NOT dispatch job when OTLP is disabled (default)
    // -------------------------------------------------------------------------

    public function testLogDoesNotDispatchJobWhenOtlpDisabled(): void
    {
        Config::set('opentelemetry.enabled', false);

        $this->repository->shouldReceive('add')->once();

        $this->tx_service
            ->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback();
            });

        $this->service->log(
            $this->user,
            TwoFactorAuditLog::EventChallengeSucceeded,
            TwoFactorAuditLog::MethodEmailOtp,
            '127.0.0.1'
        );

        Queue::assertNotPushed(EmitAuditLogJob::class);
    }

    // -------------------------------------------------------------------------
    // log() accepts null metadata
    // -------------------------------------------------------------------------

    public function testLogAcceptsNullMetadata(): void
    {
        /** @var TwoFactorAuditLog|null $persisted */
        $persisted = null;

        $this->repository
            ->shouldReceive('add')
            ->once()
            ->withArgs(function (TwoFactorAuditLog $log) use (&$persisted) {
                $persisted = $log;
                return true;
            });

        $this->tx_service
            ->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback();
            });

        $this->service->log(
            $this->user,
            TwoFactorAuditLog::EventChallengeIssued,
            TwoFactorAuditLog::MethodTotp,
            '192.168.1.1',
            null
        );

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->getMetadata());
    }

    // -------------------------------------------------------------------------
    // invalid event type throws InvalidArgumentException
    // -------------------------------------------------------------------------

    public function testInvalidEventTypeThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->shouldNotReceive('add');

        $this->service->log(
            $this->user,
            'not_a_valid_event',
            TwoFactorAuditLog::MethodEmailOtp,
            '127.0.0.1'
        );
    }

    // -------------------------------------------------------------------------
    // invalid method throws InvalidArgumentException
    // -------------------------------------------------------------------------

    public function testInvalidMethodThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repository->shouldNotReceive('add');

        $this->service->log(
            $this->user,
            TwoFactorAuditLog::EventChallengeIssued,
            'not_a_valid_method',
            '127.0.0.1'
        );
    }
}
