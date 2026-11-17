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
use App\libs\Auth\Models\UserTrustedDevice;
use App\Services\Auth\ITwoFactorAuditService;
use App\Services\Auth\RecoveryCodeService;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\Repositories\IUserRepository;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
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

    private function buildUser(int $id): \Mockery\MockInterface
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getTwoFactorMethod')->andReturn(User::MFAMethod_OTP);
        return $user;
    }

    private function expectFullDisable(\Mockery\MockInterface $user): void
    {
        $user->shouldReceive('disable2FA')->once();
        $this->user_repo->shouldReceive('add')->once()->with($user, false);
        $this->code_repo->shouldReceive('deleteAllForUser')->once()->with($user)->andReturn(10);
        $this->device_repo->shouldReceive('revokeAllActiveForUser')->once()->with($user)
            ->andReturn([new UserTrustedDevice(), new UserTrustedDevice()]);
        $this->audit_service->shouldReceive('log')
            ->twice()
            ->with($user, TwoFactorAuditLog::EventDeviceRevoked, User::MFAMethod_OTP, Mockery::type('string'), Mockery::type('array'));
    }

    public function testConsoleActorNeedsNoPassword(): void
    {
        $user = $this->buildUser(1);
        $user->shouldNotReceive('checkPassword');
        $this->expectFullDisable($user);

        $this->service->disableTwoFactor($user, null, null);
    }

    public function testAdminActorNeedsNoPassword(): void
    {
        $user = $this->buildUser(1);
        $user->shouldNotReceive('checkPassword');
        $admin = $this->buildUser(2);
        $this->expectFullDisable($user);

        $this->service->disableTwoFactor($user, null, $admin);
    }

    public function testSelfServiceWithCorrectPassword(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('checkPassword')->once()->with('secret')->andReturn(true);
        $this->expectFullDisable($user);

        $this->service->disableTwoFactor($user, ' secret ', $user);
    }

    public function testSelfServiceWithWrongPasswordChangesNothing(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('checkPassword')->once()->andReturn(false);
        $user->shouldNotReceive('disable2FA');
        $this->tx_service->shouldNotReceive('transaction');
        $this->code_repo->shouldNotReceive('deleteAllForUser');
        $this->device_repo->shouldNotReceive('revokeAllActiveForUser');
        $this->audit_service->shouldNotReceive('log');

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, 'wrong', $user);
    }

    public function testSelfServiceWithoutPasswordIsRejected(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('checkPassword')->once()->with('')->andReturn(false);
        $user->shouldNotReceive('disable2FA');

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, null, $user);
    }

    /**
     * A passwordless (social-only) user can never satisfy the self-service
     * password check: checkPassword() is false for an empty password, whatever
     * is supplied. The caller must obtain another proof of identity (a recent
     * 2FA challenge) and call this service as console/admin instead.
     * See the IRecoveryCodeService::disableTwoFactor PHPDoc.
     */
    public function testSelfServiceForPasswordlessUserIsRejectedAndChangesNothing(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('hasPasswordSet')->andReturn(false);
        $user->shouldReceive('checkPassword')->once()->andReturn(false);
        $user->shouldNotReceive('disable2FA');
        $this->tx_service->shouldNotReceive('transaction');
        $this->code_repo->shouldNotReceive('deleteAllForUser');
        $this->device_repo->shouldNotReceive('revokeAllActiveForUser');
        $this->audit_service->shouldNotReceive('log');

        $this->expectException(ValidationException::class);
        $this->service->disableTwoFactor($user, 'anything', $user);
    }

    public function testNoActiveDevicesLogsNoDeviceRevokedEvent(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('disable2FA')->once();
        $this->user_repo->shouldReceive('add')->once();
        $this->code_repo->shouldReceive('deleteAllForUser')->once()->andReturn(0);
        $this->device_repo->shouldReceive('revokeAllActiveForUser')->once()->andReturn([]);
        $this->audit_service->shouldNotReceive('log');

        $this->service->disableTwoFactor($user, null, null);
    }

    public function testAuditFailureDoesNotFailTheOperation(): void
    {
        $user = $this->buildUser(1);
        $user->shouldReceive('disable2FA')->once();
        $this->user_repo->shouldReceive('add')->once();
        $this->code_repo->shouldReceive('deleteAllForUser')->once()->andReturn(0);
        $this->device_repo->shouldReceive('revokeAllActiveForUser')->once()->andReturn([new UserTrustedDevice()]);
        $this->audit_service->shouldReceive('log')->once()->andThrow(new \RuntimeException('audit down'));

        $this->service->disableTwoFactor($user, null, null);
    }
}
