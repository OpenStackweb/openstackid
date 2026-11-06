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

use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Models\IGroupSlugs;
use App\libs\Auth\Repositories\IGroupRepository;
use App\Mail\TwoFactorEnabledByAdminMail;
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\IRecoveryCodeService;
use Auth\AuthHelper;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Server admin managing the 2FA of another user through PUT /api/v1/users/{id}
 * (Api\UserApiController@update, UserService::update).
 *
 * @package Tests
 */
final class AdminUserTwoFactorUpdateTest extends BrowserKitTestCase
{
    private User $admin;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->admin = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->be($this->admin);
        Session::start();
    }

    private function plainUser(bool $enrolled, bool $email_verified = true): User
    {
        $user = UserFactory::build([
            'first_name'     => 'Plain',
            'last_name'      => 'Target',
            'email'          => 'target.' . uniqid() . '@test.invalid',
            'active'         => true,
            'email_verified' => $email_verified,
            'identifier'     => 'target.' . uniqid(),
            'password'       => '1Qaz2wsx!',
            'password_enc'   => AuthHelper::AlgSHA1_V2_4,
        ]);
        EntityManager::persist($user);
        EntityManager::flush();
        if ($enrolled) {
            $this->app->make(IRecoveryCodeService::class)->enableTwoFactorAndGenerateCodes($user, User::MFAMethod_OTP);
            $this->app->make(IDeviceTrustService::class)->trustDevice($user, 'agent-a', '127.0.0.1');
        }
        return $user;
    }

    private function updateUser(User $target, array $payload)
    {
        return $this->action('PUT', 'Api\\UserApiController@update', ['id' => $target->getId()], $payload, [], []);
    }

    private function row(User $user): object
    {
        return DB::table('users')->where('id', $user->getId())->first();
    }

    private function lastAudit(User $user): ?array
    {
        $row = DB::table('two_factor_audit_log')->where('user_id', $user->getId())
            ->where('event_type', 'enrollment_changed')->orderByDesc('id')->first();
        return $row ? json_decode($row->metadata, true) : null;
    }

    public function testAdminEnablesTwoFactorWithoutRecoveryCodesAndNotifiesTheUser(): void
    {
        $user = $this->plainUser(false);

        $this->updateUser($user, ['two_factor_enabled' => 1]);

        $this->assertResponseStatus(201);
        $row = $this->row($user);
        $this->assertSame(1, (int)$row->two_factor_enabled);
        $this->assertNotNull($row->two_factor_enforced_at);
        $this->assertSame(0, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
        $audit = $this->lastAudit($user);
        $this->assertTrue($audit['enabled']);
        $this->assertSame($this->admin->getId(), $audit['actor_id']);
        $this->assertSame('admin', $audit['reason']);
        Mail::assertQueued(TwoFactorEnabledByAdminMail::class, 1);
    }

    public function testAdminDisablesTwoFactorWithTheSameSideEffectsAsSelfService(): void
    {
        $user = $this->plainUser(true);

        $this->updateUser($user, ['two_factor_enabled' => 0]);

        $this->assertResponseStatus(201);
        $row = $this->row($user);
        $this->assertSame(0, (int)$row->two_factor_enabled);
        $this->assertNull($row->two_factor_enforced_at);
        $this->assertSame(0, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
        $this->assertSame(0, DB::table('user_trusted_devices')
            ->where('user_id', $user->getId())->where('is_revoked', 0)->count());
        $audit = $this->lastAudit($user);
        $this->assertFalse($audit['enabled']);
        $this->assertSame($this->admin->getId(), $audit['actor_id']);
        $this->assertSame('admin', $audit['reason']);
        Mail::assertNotQueued(TwoFactorEnabledByAdminMail::class);
    }

    public function testDisablingAGroupEnforcedUserReturns412AndSavesNothing(): void
    {
        $user = $this->plainUser(true);
        $group = $this->app->make(IGroupRepository::class)->getOneBySlug(IGroupSlugs::SponsorUsersGroup);
        $user->addToGroup($group);
        EntityManager::flush();
        Config::set('two_factor.enforced_groups', [IGroupSlugs::SponsorUsersGroup]);

        $this->updateUser($user, ['two_factor_enabled' => 0, 'first_name' => 'Renamed']);

        $this->assertResponseStatus(412);
        EntityManager::clear();
        $this->assertSame('Plain', $this->row($user)->first_name, 'the rest of the update must be rolled back');
        $this->assertSame(1, (int)$this->row($user)->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
    }

    public function testDisablingWhileAddingAnEnforcedGroupInTheSameSaveReturns412AndSavesNothing(): void
    {
        $user = $this->plainUser(true);
        $group = $this->app->make(IGroupRepository::class)->getOneBySlug(IGroupSlugs::SponsorUsersGroup);
        Config::set('two_factor.enforced_groups', [IGroupSlugs::SponsorUsersGroup]);

        $this->updateUser($user, [
            'two_factor_enabled' => 0,
            'first_name'         => 'Renamed',
            'groups'             => [$group->getId()],
        ]);

        $this->assertResponseStatus(412);
        EntityManager::clear();
        $this->assertSame('Plain', $this->row($user)->first_name, 'the rest of the update must be rolled back');
        $this->assertSame(0, DB::table('user_groups')
            ->where('user_id', $user->getId())->where('group_id', $group->getId())->count(),
            'the group change must be rolled back');
        $this->assertSame(1, (int)$this->row($user)->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
    }

    public function testDisablingWhileRemovingTheEnforcedGroupInTheSameSaveSucceeds(): void
    {
        $user = $this->plainUser(true);
        $group = $this->app->make(IGroupRepository::class)->getOneBySlug(IGroupSlugs::SponsorUsersGroup);
        $user->addToGroup($group);
        EntityManager::flush();
        Config::set('two_factor.enforced_groups', [IGroupSlugs::SponsorUsersGroup]);

        // the admin form always sends the full list: the enforced group is no longer in it
        $raw = $this->app->make(IGroupRepository::class)->getOneBySlug(IGroupSlugs::RawUsersGroup);
        $this->updateUser($user, ['two_factor_enabled' => 0, 'groups' => [$raw->getId()]]);

        $this->assertResponseStatus(201);
        EntityManager::clear();
        $this->assertSame(0, DB::table('user_groups')
            ->where('user_id', $user->getId())->where('group_id', $group->getId())->count());
        $this->assertSame(0, (int)$this->row($user)->two_factor_enabled);
        $this->assertSame(0, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
    }

    public function testEnablingForAnUnverifiedEmailReturns412AndSavesNothing(): void
    {
        $user = $this->plainUser(false, false);

        $this->updateUser($user, ['two_factor_enabled' => 1, 'first_name' => 'Renamed']);

        $this->assertResponseStatus(412);
        EntityManager::clear();
        $this->assertSame('Plain', $this->row($user)->first_name, 'the rest of the update must be rolled back');
        $this->assertSame(0, (int)$this->row($user)->two_factor_enabled);
        Mail::assertNotQueued(TwoFactorEnabledByAdminMail::class);
    }

    public function testEnablingTogetherWithEmailVerifiedInTheSameSaveSucceeds(): void
    {
        $user = $this->plainUser(false, false);

        $this->updateUser($user, ['two_factor_enabled' => 1, 'email_verified' => 1]);

        $this->assertResponseStatus(201);
        $this->assertSame(1, (int)$this->row($user)->two_factor_enabled);
        Mail::assertQueued(TwoFactorEnabledByAdminMail::class, 1);
    }

    public function testAPayloadWithoutTheKeyLeavesTwoFactorUntouched(): void
    {
        $user = $this->plainUser(true);

        $this->updateUser($user, ['first_name' => 'Renamed']);

        $this->assertResponseStatus(201);
        $this->assertSame(1, (int)$this->row($user)->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
        $this->assertSame(0, DB::table('user_trusted_devices')
            ->where('user_id', $user->getId())->where('is_revoked', 1)->count());
        Mail::assertNotQueued(TwoFactorEnabledByAdminMail::class);
    }

    public function testAnUnchangedValueIsIgnored(): void
    {
        $user = $this->plainUser(true);

        $this->updateUser($user, ['first_name' => 'Renamed', 'two_factor_enabled' => 1]);

        $this->assertResponseStatus(201);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
        $this->assertSame(1, DB::table('two_factor_audit_log')
            ->where('user_id', $user->getId())->where('event_type', 'enrollment_changed')->count(),
            'only the enrollment of the fixture, no second row');
        Mail::assertNotQueued(TwoFactorEnabledByAdminMail::class);
    }

    public function testAUserCannotTurnOffTheirOwnTwoFactorThroughTheProfileUpdate(): void
    {
        $user = $this->plainUser(true);
        $this->be($user);

        $this->action('PUT', 'Api\\UserApiController@updateMe', [], ['two_factor_enabled' => 0], [], []);

        $this->assertSame(1, (int)$this->row($user)->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
    }

    public function testAnAdminCannotChangeTheirOwnTwoFactorThroughTheUserCrud(): void
    {
        $admin = $this->plainUser(true);
        $group = $this->app->make(IGroupRepository::class)->getOneBySlug(IGroupSlugs::SuperAdminGroup);
        $admin->addToGroup($group);
        EntityManager::flush();
        $this->be($admin);

        $this->updateUser($admin, ['two_factor_enabled' => 0]);

        $this->assertSame(1, (int)$this->row($admin)->two_factor_enabled);
    }
}
