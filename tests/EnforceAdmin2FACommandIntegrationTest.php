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
use App\Mail\TwoFactorEnforcedMail;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class EnforceAdmin2FACommandIntegrationTest
 * Runs idp:enforce-admin-2fa through Artisan against a real group-enforced
 * admin and asserts the database side effects.
 * @package Tests
 */
final class EnforceAdmin2FACommandIntegrationTest extends BrowserKitTestCase
{
    private User $admin;

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
        Mail::fake();

        $user = new User();
        $user->setEmail('enforce-admin@nomail.com');
        $user->setFirstName('Enforce');
        $user->setLastName('Admin');
        $user->setIdentifier('enforce-admin@nomail.com');
        $user->setPassword('P@sswordS3cret');
        $user->verifyEmail(false);
        $user->addToGroup($this->createEnforcedGroup());
        EntityManager::persist($user);
        EntityManager::flush();
        $this->admin = $user;
    }

    private function userRow(): object
    {
        return DB::table('users')->where('id', $this->admin->getId())->first();
    }

    private function codeHashes(): array
    {
        return DB::table('user_recovery_codes')
            ->where('user_id', $this->admin->getId())
            ->orderBy('id')
            ->pluck('code_hash')
            ->all();
    }

    public function testEnforceProcessesEnforcedAdmin(): void
    {
        $before = $this->userRow();

        $code = Artisan::call('idp:enforce-admin-2fa');

        $this->assertSame(0, $code);
        $this->assertCount(10, $this->codeHashes());
        $this->assertSame(1, DB::table('two_factor_audit_log')
            ->where('user_id', $this->admin->getId())
            ->where('event_type', TwoFactorAuditLog::EventEnrollmentChanged)
            ->count());
        $metadata = DB::table('two_factor_audit_log')
            ->where('user_id', $this->admin->getId())
            ->where('event_type', TwoFactorAuditLog::EventEnrollmentChanged)
            ->value('metadata');
        $this->assertSame(['source' => 'enforce-admin-2fa'], json_decode($metadata, true));
        Mail::assertSent(TwoFactorEnforcedMail::class, 1);

        // enforcement is derived from the group: stored flags stay untouched
        $after = $this->userRow();
        $this->assertSame($before->two_factor_enabled, $after->two_factor_enabled);
        $this->assertSame($before->two_factor_method, $after->two_factor_method);
        $this->assertSame($before->two_factor_enforced_at, $after->two_factor_enforced_at);
    }

    public function testSecondRunSkipsAndKeepsExistingCodes(): void
    {
        Artisan::call('idp:enforce-admin-2fa');
        $hashes = $this->codeHashes();

        Artisan::call('idp:enforce-admin-2fa');

        $this->assertStringContainsString('processed 0, skipped 1, errors 0', Artisan::output());
        $this->assertSame($hashes, $this->codeHashes());
        Mail::assertSent(TwoFactorEnforcedMail::class, 1);
    }

    public function testDryRunWritesNothing(): void
    {
        $code = Artisan::call('idp:enforce-admin-2fa', ['--dry-run' => true]);

        $this->assertSame(0, $code);
        $this->assertStringContainsString('processed 1, skipped 0, errors 0', Artisan::output());
        $this->assertCount(0, $this->codeHashes());
        $this->assertSame(0, DB::table('two_factor_audit_log')->where('user_id', $this->admin->getId())->count());
        Mail::assertNothingSent();
    }
}
