<?php namespace Tests\unit;
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

use App\libs\Auth\Models\TwoFactorAuditLog;
use App\Services\Auth\ITwoFactorAuditService;
use App\Services\Auth\RecoveryCodeService;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\Repositories\IUserRepository;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use Auth\MFAConstants;
use Illuminate\Support\Facades\Session;
use Mockery;
use models\exceptions\ValidationException;
use Tests\BrowserKitTestCase;
use Utils\Db\ITransactionService;

/**
 * Class RecoveryCodeServiceDisableTwoFactorTest
 * @package Tests\unit
 */
final class RecoveryCodeServiceDisableTwoFactorTest extends BrowserKitTestCase
{
    private RecoveryCodeService $service;

    /** @var \Mockery\MockInterface&IUserRecoveryCodeRepository */
    private $code_repo;

    /** @var \Mockery\MockInterface&IUserRepository */
    private $user_repo;

    /** @var \Mockery\MockInterface&IUserTrustedDeviceRepository */
    private $device_repo;

    /** @var \Mockery\MockInterface&ITwoFactorAuditService */
    private $audit_service;

    /** @var \Mockery\MockInterface&ITransactionService */
    private $tx_service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->code_repo = Mockery::mock(IUserRecoveryCodeRepository::class);
        $this->user_repo = Mockery::mock(IUserRepository::class);
        $this->device_repo = Mockery::mock(IUserTrustedDeviceRepository::class);
        $this->audit_service = Mockery::mock(ITwoFactorAuditService::class);
        $this->tx_service = Mockery::mock(ITransactionService::class);
        $this->tx_service->shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb())->byDefault();

        $this->service = new RecoveryCodeService(
            $this->code_repo,
            $this->user_repo,
            $this->device_repo,
            $this->tx_service,
            $this->audit_service
        );
    }

    protected function tearDown(): void
    {
        // Mockery expectations are verified on close(): count them as assertions
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
        parent::tearDown();
    }

    private function buildUser(int $id, bool $enforced = false, bool $hasPassword = true): \Mockery\MockInterface
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);
        $user->shouldReceive('isTwoFactorEnforcedByGroup')->andReturn($enforced);
        $user->shouldReceive('hasPasswordSet')->andReturn($hasPassword);
        return $user;
    }

    private function expectFullDisable(\Mockery\MockInterface $user, ?int $actor_id, string $reason): void
    {
        $this->code_repo->shouldReceive('lockUserRow')->once()->with($user)->ordered();
        $user->shouldReceive('disable2FA')->once()->ordered();
        $user->shouldReceive('setRecoveryCodesAcknowledgedAt')->once()->with(null);
        $this->user_repo->shouldReceive('add')->once()->with($user, false);
        $this->code_repo->shouldReceive('deleteAllForUser')->once()->with($user)->andReturn(10);
        $this->device_repo->shouldReceive('revokeAllForUser')->once()->with($user);
        $this->audit_service->shouldReceive('log')
            ->once()
            ->with(
                $user,
                TwoFactorAuditLog::EventEnrollmentChanged,
                User::MFAMethod_OTP,
                Mockery::type('string'),
                ['enabled' => false, 'actor_id' => $actor_id, 'reason' => $reason]
            );
    }

    private function expectNothingChanges(\Mockery\MockInterface $user): void
    {
        $user->shouldNotReceive('disable2FA');
        $this->tx_service->shouldNotReceive('transaction');
        $this->code_repo->shouldNotReceive('deleteAllForUser');
        $this->device_repo->shouldNotReceive('revokeAllForUser');
        $this->audit_service->shouldNotReceive('log');
    }

    public function testConsoleActorNeedsNoPassword(): void
    {
        $user = $this->buildUser(1);
        $user->shouldNotReceive('checkPassword');
        $this->expectFullDisable($user, null, 'console');

        $this->service->disableTwoFactor($user, null, null);
    }

    public function testConsoleActorMayResetAGroupEnforcedUser(): void
    {
        $user = $this->buildUser(1, true);
        $this->expectFullDisable($user, null, 'console');

        $this->service->disableTwoFactor($user, null, null);
    }

    public function testAdminActorNeedsNoPassword(): void
    {
        $user = $this->buildUser(1);
        $user->shouldNotReceive('checkPassword');
        $admin = $this->buildUser(2);
        $this->expectFullDisable($user, 2, 'admin');

        $this->service->disableTwoFactor($user, null, $admin);
    }

    public function testAdminActorCannotDisableAGroupEnforcedUser(): void
    {
        $user = $this->buildUser(1, true);
        $admin = $this->buildUser(2);
        $this->expectNothingChanges($user);

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, null, $admin);
    }

    public function testSelfServiceWithCorrectPassword(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('checkPassword')->once()->with('secret')->andReturn(true);
        $this->expectFullDisable($user, 1, 'self');

        $this->service->disableTwoFactor($user, ' secret ', $user);
    }

    public function testSelfServiceWithWrongPasswordChangesNothing(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('checkPassword')->once()->andReturn(false);
        $this->expectNothingChanges($user);

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, 'wrong', $user);
    }

    public function testSelfServiceOnAGroupEnforcedUserIsRefusedBeforeCheckingThePassword(): void
    {
        $user = $this->buildUser(1, true);
        $user->shouldNotReceive('checkPassword');
        $this->expectNothingChanges($user);

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, 'secret', $user);
    }

    public function testSelfServiceWithoutPasswordOutsideTheEnrollmentWindowIsRejected(): void
    {
        Session::forget(MFAConstants::SESSION_KEY_COMPLETED_AT);
        $user = $this->buildUser(1, false, false);
        $user->shouldNotReceive('checkPassword');
        $this->expectNothingChanges($user);

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, null, $user);
    }

    public function testSelfServiceWithoutPasswordInsideTheEnrollmentWindow(): void
    {
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => 1, 'at' => time() - 30]);
        $user = $this->buildUser(1, false, false);
        $user->shouldNotReceive('checkPassword');
        $this->expectFullDisable($user, 1, 'self');

        $this->service->disableTwoFactor($user, null, $user);
    }

    public function testSelfServiceWithoutPasswordAndAWindowOfAnotherUserIsRejected(): void
    {
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => 99, 'at' => time() - 30]);
        $user = $this->buildUser(1, false, false);
        $this->expectNothingChanges($user);

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, null, $user);
    }

    public function testAuditFailureDoesNotFailTheOperation(): void
    {
        $user = $this->buildUser(1);
        $this->code_repo->shouldReceive('lockUserRow')->once();
        $user->shouldReceive('disable2FA')->once();
        $user->shouldReceive('setRecoveryCodesAcknowledgedAt')->once();
        $this->user_repo->shouldReceive('add')->once();
        $this->code_repo->shouldReceive('deleteAllForUser')->once()->andReturn(0);
        $this->device_repo->shouldReceive('revokeAllForUser')->once();
        $this->audit_service->shouldReceive('log')->once()->andThrow(new \RuntimeException('audit down'));

        $this->service->disableTwoFactor($user, null, null);
    }
}
