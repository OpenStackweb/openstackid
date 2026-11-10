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
 * Orchestration of idp:enforce-admin-2fa with the audit service mocked; the real DB is only used to resolve the group members.
 * @package Tests\unit\Console
 */
final class EnforceAdmin2FACommandTest extends BrowserKitTestCase
{
    private Group $group;

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
        $this->audit_service = Mockery::mock(ITwoFactorAuditService::class);
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

    private function markNotified(User $user): void
    {
        $log = new TwoFactorAuditLog();
        $log->setUser($user);
        $log->setEventType(TwoFactorAuditLog::EventEnrollmentChanged);
        $log->setMethod(TwoFactorAuditLog::MethodEmailOtp);
        $log->setIpAddress('127.0.0.1');
        $log->setUserAgent('');
        $log->setMetadata(['source' => 'enforce-admin-2fa']);
        EntityManager::persist($log);
        EntityManager::flush();
    }

    public function testProcessesUsersNotYetNotified(): void
    {
        $user = $this->createUser('enforce-a@nomail.com', true);

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

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 1, skipped 0, errors 0', Artisan::output());
        Mail::assertSent(TwoFactorEnforcedMail::class, 1);
    }

    public function testSkipsUsersAlreadyNotified(): void
    {
        $this->markNotified($this->createUser('enforce-b@nomail.com', true));

        $this->audit_service->shouldNotReceive('log');

        $code = Artisan::call('idp:enforce-admin-2fa');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 0, skipped 1, errors 0', Artisan::output());
        Mail::assertNothingSent();
    }

    public function testRefusesToRunWhenTwoFactorIsDisabledGlobally(): void
    {
        config(['two_factor.enabled' => false]);
        $this->createUser('enforce-h@nomail.com', true);

        $this->audit_service->shouldNotReceive('log');

        foreach ([[], ['--dry-run' => true]] as $options) {
            $code = Artisan::call('idp:enforce-admin-2fa', $options);

            $this->assertSame(1, $code);
            $this->assertStringContainsString('two_factor.enabled is false', Artisan::output());
        }
        Mail::assertNothingSent();
    }

    public function testIgnoresUsersOutsideEnforcedGroups(): void
    {
        $this->createUser('enforce-c@nomail.com', false);

        $this->audit_service->shouldNotReceive('log');

        Artisan::call('idp:enforce-admin-2fa');

        $this->assertStringContainsString('processed 0, skipped 0, errors 0', Artisan::output());
        Mail::assertNothingSent();
    }

    public function testDryRunWritesNothingAndReportsSameCounts(): void
    {
        $this->createUser('enforce-d@nomail.com', true);
        $this->markNotified($this->createUser('enforce-e@nomail.com', true));

        $this->audit_service->shouldNotReceive('log');

        $code = Artisan::call('idp:enforce-admin-2fa', ['--dry-run' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 1, skipped 1, errors 0', Artisan::output());
        Mail::assertNothingSent();
    }

    public function testMailFailureOnOneUserDoesNotStopTheRunNorAuditsIt(): void
    {
        $this->createUser('enforce-f@nomail.com', true);
        $this->createUser('enforce-g@nomail.com', true);

        Mail::swap(new class(new \Illuminate\Mail\MailManager(app())) extends \Illuminate\Support\Testing\Fakes\MailFake {
            private int $calls = 0;

            public function send($view, array $data = [], $callback = null)
            {
                if (++$this->calls === 1) {
                    throw new \RuntimeException('smtp down');
                }
                parent::send($view, $data, $callback);
            }
        });
        // only the user whose mail was sent gets an audit row
        $this->audit_service->shouldReceive('log')->once();

        $code = Artisan::call('idp:enforce-admin-2fa');

        $this->assertSame(1, $code);
        $this->assertStringContainsString('processed 1, skipped 0, errors 1', Artisan::output());
        Mail::assertSent(TwoFactorEnforcedMail::class, 1);
    }
}
