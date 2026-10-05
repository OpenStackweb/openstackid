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
use App\Mail\TwoFactorEnforcedMail;
use App\Services\Auth\IRecoveryCodeService;
use App\Services\Auth\ITwoFactorAuditService;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Mockery;
use Tests\BrowserKitTestCase;

/**
 * Class EnforceAdmin2FACommandTest
 * Orchestration of idp:enforce-admin-2fa with the recovery code and audit
 * services mocked; the real DB is only used to resolve the group members.
 * @package Tests\unit\Console
 */
final class EnforceAdmin2FACommandTest extends BrowserKitTestCase
{
    private Group $group;

    /** @var \Mockery\MockInterface&IRecoveryCodeService */
    private $recovery_service;

    /** @var \Mockery\MockInterface&ITwoFactorAuditService */
    private $audit_service;

    private const TestGroupSlug = 'enforce-2fa-test-group';

    private function createEnforcedGroup(): Group
    {
        $group = new Group();
        $group->setName(self::TestGroupSlug);
        $group->setSlug(self::TestGroupSlug);
        $group->setDefault(false);
        $group->setActive(true);
        EntityManager::persist($group);
        EntityManager::flush();
        return $group;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['two_factor.enforced_groups' => [self::TestGroupSlug]]);
        $this->group = $this->createEnforcedGroup();
        Mail::fake();
        $this->recovery_service = Mockery::mock(IRecoveryCodeService::class);
        $this->audit_service = Mockery::mock(ITwoFactorAuditService::class);
        $this->app->instance(IRecoveryCodeService::class, $this->recovery_service);
        $this->app->instance(ITwoFactorAuditService::class, $this->audit_service);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createUser(string $email, bool $in_group): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($email);
        $user->setLastName($email);
        $user->setIdentifier($email);
        $user->setPassword('P@sswordS3cret');
        $user->verifyEmail(false);
        if ($in_group) {
            $user->addToGroup($this->group);
        }
        EntityManager::persist($user);
        EntityManager::flush();
        return $user;
    }

    public function testProcessesUsersWithoutUnusedCodes(): void
    {
        $user = $this->createUser('enforce-a@nomail.com', true);

        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->once()->andReturn(0);
        $this->recovery_service->shouldReceive('generateRecoveryCodes')->once()->andReturn(['AAAA-BBBB']);
        $this->audit_service->shouldReceive('log')
            ->once()
            ->with(
                Mockery::on(fn($u) => $u->getId() === $user->getId()),
                TwoFactorAuditLog::EventEnrollmentChanged,
                TwoFactorAuditLog::MethodEmailOtp,
                Mockery::type('string'),
                ['source' => 'enforce-admin-2fa']
            );

        $code = Artisan::call('idp:enforce-admin-2fa');
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 1, skipped 0, errors 0', $output);
        // plaintext codes are shown once and never printed
        $this->assertStringNotContainsString('AAAA-BBBB', $output);
        Mail::assertSent(TwoFactorEnforcedMail::class, 1);
    }

    public function testSkipsUsersWithUnusedCodes(): void
    {
        $this->createUser('enforce-b@nomail.com', true);

        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->once()->andReturn(10);
        $this->recovery_service->shouldNotReceive('generateRecoveryCodes');
        $this->audit_service->shouldNotReceive('log');

        $code = Artisan::call('idp:enforce-admin-2fa');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 0, skipped 1, errors 0', Artisan::output());
        Mail::assertNothingSent();
    }

    public function testIgnoresUsersOutsideEnforcedGroups(): void
    {
        $this->createUser('enforce-c@nomail.com', false);

        $this->recovery_service->shouldNotReceive('countUnusedRecoveryCodes');
        $this->recovery_service->shouldNotReceive('generateRecoveryCodes');

        Artisan::call('idp:enforce-admin-2fa');

        $this->assertStringContainsString('processed 0, skipped 0, errors 0', Artisan::output());
    }

    public function testDryRunWritesNothingAndReportsSameCounts(): void
    {
        $this->createUser('enforce-d@nomail.com', true);
        $this->createUser('enforce-e@nomail.com', true);

        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->twice()->andReturn(0, 10);
        $this->recovery_service->shouldNotReceive('generateRecoveryCodes');
        $this->audit_service->shouldNotReceive('log');

        $code = Artisan::call('idp:enforce-admin-2fa', ['--dry-run' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 1, skipped 1, errors 0', Artisan::output());
        Mail::assertNothingSent();
    }

    public function testErrorOnOneUserDoesNotStopTheRun(): void
    {
        $this->createUser('enforce-f@nomail.com', true);
        $this->createUser('enforce-g@nomail.com', true);

        $this->recovery_service->shouldReceive('countUnusedRecoveryCodes')->twice()->andReturn(0);
        $this->recovery_service->shouldReceive('generateRecoveryCodes')
            ->twice()
            ->andReturnUsing(
                fn() => throw new \RuntimeException('boom'),
                fn() => ['AAAA-BBBB']
            );
        $this->audit_service->shouldReceive('log')->once();

        $code = Artisan::call('idp:enforce-admin-2fa');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('processed 1, skipped 0, errors 1', Artisan::output());
        Mail::assertSent(TwoFactorEnforcedMail::class, 1);
    }
}
