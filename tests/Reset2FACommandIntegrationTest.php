<?php namespace Tests;
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
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\IRecoveryCodeService;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class Reset2FACommandIntegrationTest
 * Runs idp:reset-2fa through Artisan against a self-enrolled user and a
 * group-enforced admin and asserts the database side effects.
 * @package Tests
 */
final class Reset2FACommandIntegrationTest extends BrowserKitTestCase
{
    private const GroupSlug = 'reset-2fa-test-group';

    private User $self_enrolled;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['two_factor.enforced_groups' => [self::GroupSlug]]);

        $group = new Group();
        $group->setName(self::GroupSlug);
        $group->setSlug(self::GroupSlug);
        $group->setDefault(false);
        $group->setActive(true);
        EntityManager::persist($group);
        EntityManager::flush();

        $this->self_enrolled = $this->enrolledUser('reset-self@nomail.com');
        $this->admin = $this->enrolledUser('reset-admin@nomail.com');
        $this->admin->addToGroup($group);
        EntityManager::flush();
    }

    /**
     * enrolled by the user: stored flag, 10 recovery codes and 2 trusted devices
     */
    private function enrolledUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Reset');
        $user->setLastName('TwoFactor');
        $user->setIdentifier($email);
        $user->setPassword('P@sswordS3cret');
        $user->verifyEmail(false);
        EntityManager::persist($user);
        EntityManager::flush();

        $this->app->make(IRecoveryCodeService::class)->enableTwoFactorAndGenerateCodes($user, User::MFAMethod_OTP);
        $devices = $this->app->make(IDeviceTrustService::class);
        $devices->trustDevice($user, 'agent-a', '127.0.0.1');
        $devices->trustDevice($user, 'agent-b', '127.0.0.1');
        return $user;
    }

    private function reloaded(User $user): User
    {
        EntityManager::clear();
        return EntityManager::getRepository(User::class)->find($user->getId());
    }

    private function codeCount(User $user): int
    {
        return DB::table('user_recovery_codes')->where('user_id', $user->getId())->count();
    }

    public function testWithoutReasonExitsNonZeroAndChangesNothing(): void
    {
        $code = Artisan::call('idp:reset-2fa', ['email' => 'reset-self@nomail.com']);

        $this->assertSame(1, $code);
        $row = DB::table('users')->where('id', $this->self_enrolled->getId())->first();
        $this->assertSame(1, (int)$row->two_factor_enabled);
        $this->assertNotNull($row->two_factor_enforced_at);
        $this->assertSame(10, $this->codeCount($this->self_enrolled));
        $this->assertSame(0, DB::table('user_trusted_devices')
            ->where('user_id', $this->self_enrolled->getId())->where('is_revoked', 1)->count());
        $this->assertSame(0, DB::table('two_factor_audit_log')
            ->where('user_id', $this->self_enrolled->getId())
            ->where('event_type', TwoFactorAuditLog::EventSettingsChanged)->count());
    }

    public function testResetClearsSelfEnrolledUser(): void
    {
        $code = Artisan::call('idp:reset-2fa', ['email' => 'reset-self@nomail.com', '--reason' => 'ticket 123']);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $id = $this->self_enrolled->getId();
        $row = DB::table('users')->where('id', $id)->first();
        $this->assertSame(0, (int)$row->two_factor_enabled);
        $this->assertNull($row->two_factor_enforced_at);
        $this->assertSame(0, $this->codeCount($this->self_enrolled));

        $devices = DB::table('user_trusted_devices')->where('user_id', $id)->get();
        $this->assertCount(2, $devices);
        foreach ($devices as $device) {
            $this->assertSame(1, (int)$device->is_revoked);
        }

        $audit = DB::table('two_factor_audit_log')
            ->where('user_id', $id)
            ->where('event_type', TwoFactorAuditLog::EventSettingsChanged)
            ->get();
        $this->assertCount(1, $audit);
        $metadata = json_decode($audit[0]->metadata, true);
        $this->assertSame('ticket 123', $metadata['reason']);
        $this->assertSame('console', $metadata['actor']);
        $this->assertNotEmpty($metadata['operator']);

        $this->assertStringContainsString('unused recovery codes deleted: 10', $output);
        $this->assertStringNotContainsString('2FA enforced group', $output);

        // a self-enrolled user is no longer challenged at the next password login
        $this->assertFalse($this->reloaded($this->self_enrolled)->shouldRequire2FA());
    }

    public function testGroupEnforcedAdminIsStillChallengedAfterReset(): void
    {
        $code = Artisan::call('idp:reset-2fa', ['email' => 'reset-admin@nomail.com', '--reason' => 'lost phone']);
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertSame(0, $this->codeCount($this->admin));
        $this->assertSame(0, (int)DB::table('users')->where('id', $this->admin->getId())->value('two_factor_enabled'));
        $this->assertStringContainsString('2FA enforced group', $output);

        // enforcement is derived from the group: the next login still lands on the challenge
        $this->assertTrue($this->reloaded($this->admin)->shouldRequire2FA());
    }
}
