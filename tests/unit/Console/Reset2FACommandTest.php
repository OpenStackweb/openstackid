<?php namespace Tests\unit\Console;
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
use App\Services\Auth\IRecoveryCodeService;
use App\Services\Auth\ITwoFactorAuditService;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use Illuminate\Support\Facades\Artisan;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Mockery;
use Tests\BrowserKitTestCase;

/**
 * Class Reset2FACommandTest
 * Orchestration of idp:reset-2fa with the services mocked; the real DB is
 * only used to resolve the user by email.
 * @package Tests\unit\Console
 */
final class Reset2FACommandTest extends BrowserKitTestCase
{
    private const Email = 'reset-2fa@nomail.com';

    /** @var \Mockery\MockInterface&IRecoveryCodeService */
    private $recovery_service;

    /** @var \Mockery\MockInterface&ITwoFactorAuditService */
    private $audit_service;

    /** @var \Mockery\MockInterface&IUserTrustedDeviceRepository */
    private $device_repo;

    protected function setUp(): void
    {
        parent::setUp();
        config(['two_factor.enforced_groups' => []]);
        $this->recovery_service = Mockery::mock(IRecoveryCodeService::class);
        $this->audit_service = Mockery::mock(ITwoFactorAuditService::class);
        $this->device_repo = Mockery::mock(IUserTrustedDeviceRepository::class);
        $this->app->instance(IRecoveryCodeService::class, $this->recovery_service);
        $this->app->instance(ITwoFactorAuditService::class, $this->audit_service);
        $this->app->instance(IUserTrustedDeviceRepository::class, $this->device_repo);
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
        parent::tearDown();
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail(self::Email);
        $user->setFirstName('Reset');
        $user->setLastName('TwoFactor');
        $user->setIdentifier(self::Email);
        $user->setPassword('P@sswordS3cret');
        $user->verifyEmail(false);
        EntityManager::persist($user);
        EntityManager::flush();
        return $user;
    }

    private function expectNothingTouched(): void
    {
        $this->recovery_service->shouldNotReceive('countUnusedRecoveryCodes');
        $this->recovery_service->shouldNotReceive('disableTwoFactor');
        $this->audit_service->shouldNotReceive('log');
    }

    public function testAbortsWithoutReason(): void
    {
        $this->createUser();
        $this->expectNothingTouched();

        $code = Artisan::call('idp:reset-2fa', ['email' => self::Email]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--reason option is required', Artisan::output());
    }

    public function testAbortsWithBlankReason(): void
    {
        $this->createUser();
        $this->expectNothingTouched();

        $code = Artisan::call('idp:reset-2fa', ['email' => self::Email, '--reason' => '   ']);

        $this->assertSame(1, $code);
    }

    public function testFailsWhenUserNotFound(): void
    {
        $this->expectNothingTouched();

        $code = Artisan::call('idp:reset-2fa', ['email' => 'nobody@nomail.com', '--reason' => 'ticket 123']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('nobody@nomail.com not found', Artisan::output());
    }

    public function testResetsAndAuditsWithReasonAndOperator(): void
    {
        $user = $this->createUser();
        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->once()->andReturn(7);
        $this->device_repo->shouldReceive('getActiveByUser')->once()->andReturn([1, 2]);
        $this->recovery_service->shouldReceive('disableTwoFactor')
            ->once()
            ->with(Mockery::on(fn($u) => $u->getId() === $user->getId()), null, null);
        $this->audit_service->shouldReceive('log')
            ->once()
            ->with(
                Mockery::on(fn($u) => $u->getId() === $user->getId()),
                TwoFactorAuditLog::EventSettingsChanged,
                User::MFAMethod_OTP,
                Mockery::type('string'),
                Mockery::on(fn(array $m) => $m['reason'] === 'ticket 123'
                    && $m['actor'] === 'console'
                    && !empty($m['operator']))
            );

        $code = Artisan::call('idp:reset-2fa', ['email' => self::Email, '--reason' => 'ticket 123']);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('unused recovery codes deleted: 7', $output);
        $this->assertStringContainsString('trusted devices revoked:       2', $output);
        $this->assertStringNotContainsString('2FA enforced group', $output);
    }

    public function testWarnsWhenUserIsGroupEnforced(): void
    {
        $user = $this->createUser();
        // a stand-in for group membership: the user is required by the stored flag
        $user->enable2FA(User::MFAMethod_OTP);
        EntityManager::flush();
        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->once()->andReturn(0);
        $this->device_repo->shouldReceive('getActiveByUser')->once()->andReturn([]);
        $this->recovery_service->shouldReceive('disableTwoFactor')->once();
        $this->audit_service->shouldReceive('log')->once();

        // the mocked service did not clear the flag, so shouldRequire2FA() is still true
        $code = Artisan::call('idp:reset-2fa', ['email' => self::Email, '--reason' => 'ticket 123']);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('2FA enforced group', Artisan::output());
    }

    public function testAuditFailureIsReportedAsFailure(): void
    {
        $this->createUser();
        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->once()->andReturn(0);
        $this->device_repo->shouldReceive('getActiveByUser')->once()->andReturn([]);
        $this->recovery_service->shouldReceive('disableTwoFactor')->once();
        $this->audit_service->shouldReceive('log')->once()->andThrow(new \RuntimeException('audit down'));

        $code = Artisan::call('idp:reset-2fa', ['email' => self::Email, '--reason' => 'ticket 123']);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('reset of ' . self::Email . ' was applied', Artisan::output());
    }
}
