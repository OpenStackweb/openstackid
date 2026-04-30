<?php
namespace Tests\unit;

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

use App\libs\Auth\Models\IGroupSlugs;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Config;
use models\exceptions\ValidationException;
use Tests\TestCase;

/**
 * Class UserTwoFactorTest
 * Unit tests for the 2FA-related methods on the User entity.
 */
class UserTwoFactorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('two_factor.enforced_groups', [
            IGroupSlugs::SuperAdminGroup,
            IGroupSlugs::AdminGroup,
            IGroupSlugs::OAuth2ServerAdminGroup,
            IGroupSlugs::OpenIdServerAdminsGroup,
        ]);
    }

    private function buildGroup(string $slug): Group
    {
        $group = new Group();
        $group->setName($slug);
        $group->setSlug($slug);
        return $group;
    }

    private function assignGroups(User $user, array $groups): void
    {
        $reflection = new \ReflectionClass(User::class);
        $property = $reflection->getProperty('groups');
        $property->setAccessible(true);
        $collection = $property->getValue($user);
        foreach ($groups as $group) {
            $collection->add($group);
        }
    }

    private function setEmailVerified(User $user, bool $verified): void
    {
        $reflection = new \ReflectionClass(User::class);
        $property = $reflection->getProperty('email_verified');
        $property->setAccessible(true);
        $property->setValue($user, $verified);
    }

    public function testShouldRequire2FA_superAdminUser(): void
    {
        $user = new User();
        $this->assignGroups($user, [$this->buildGroup(IGroupSlugs::SuperAdminGroup)]);

        $this->assertFalse($user->isTwoFactorEnabled());
        $this->assertTrue($user->shouldRequire2FA());
    }

    public function testShouldRequire2FA_adminUser(): void
    {
        $user = new User();
        $this->assignGroups($user, [$this->buildGroup(IGroupSlugs::AdminGroup)]);

        $this->assertFalse($user->isTwoFactorEnabled());
        $this->assertTrue($user->shouldRequire2FA());
    }

    public function testShouldRequire2FA_BelongsToAnEnforcedGroup(): void
    {
        config(['two_factor.enforced_groups' => []]);
        $this->assertSame([], config('two_factor.enforced_groups'), "Config value 'two_factor.enforced_groups' is set");

        $groups = [
            IGroupSlugs::SuperAdminGroup,
            IGroupSlugs::AdminGroup,
            IGroupSlugs::OAuth2ServerAdminGroup,
        ];
        config(['two_factor.enforced_groups' => $groups]);
        $this->assertSame($groups, config('two_factor.enforced_groups'), "Config value 'two_factor.enforced_groups' is set");

        $user = new User();
        $this->assertFalse($user->shouldRequire2FA(), "The user does not belong to any enforced group");


        $this->assignGroups($user, array_map([$this, 'buildGroup'], $groups));
        $this->assertTrue($user->belongToGroup(IGroupSlugs::SuperAdminGroup), "The user belongs to ".IGroupSlugs::SuperAdminGroup." group");
        $this->assertTrue($user->belongToGroup(IGroupSlugs::AdminGroup), "The user belongs to ".IGroupSlugs::AdminGroup." group");
        $this->assertTrue($user->belongToGroup(IGroupSlugs::OAuth2ServerAdminGroup), "The user belongs to ".IGroupSlugs::OAuth2ServerAdminGroup." group");
        $this->assertTrue($user->shouldRequire2FA(), "The user does belong to an enforced group");

        $groups = [IGroupSlugs::RawUsersGroup];
        $user = new User();
        $this->assertFalse($user->shouldRequire2FA(), "The user does not belong to any enforced group");
        $this->assignGroups($user, array_map([$this, 'buildGroup'], $groups));
        $this->assertFalse($user->shouldRequire2FA(), "The user does not belong to any enforced group");
    }

    public function testShouldRequire2FA_regularUser_enabled(): void
    {
        $user = new User();
        $this->assignGroups($user, [$this->buildGroup(IGroupSlugs::RawUsersGroup)]);
        $user->setTwoFactorEnabled(true);

        $this->assertTrue($user->shouldRequire2FA());
    }

    public function testShouldRequire2FA_regularUser_disabled(): void
    {
        $user = new User();
        $this->assignGroups($user, [$this->buildGroup(IGroupSlugs::RawUsersGroup)]);

        $this->assertFalse($user->isTwoFactorEnabled());
        $this->assertFalse($user->shouldRequire2FA());
    }

    public function testEnable2FA_validMethod(): void
    {
        $user = new User();
        // email_otp is only "available" to a user whose email is verified —
        // enable2FA() now whitelists via getAvailableTwoFactorMethods().
        $this->setEmailVerified($user, true);
        $before = new \DateTime('now', new \DateTimeZone('UTC'));

        $user->enable2FA('email_otp');

        $after = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->assertTrue($user->isTwoFactorEnabled());
        $this->assertSame('email_otp', $user->getTwoFactorMethod());
        $enforcedAt = $user->getTwoFactorEnforcedAt();
        $this->assertInstanceOf(\DateTime::class, $enforcedAt);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $enforcedAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $enforcedAt->getTimestamp());
    }

    public function testEnable2FA_invalidMethod_throws(): void
    {
        $user = new User();
        $this->expectException(ValidationException::class);
        $user->enable2FA('invalid_method');
    }

    public function testEnable2FA_phaseTwoMethod_throwsInPhaseOne(): void
    {
        // sms_otp/totp/passkey are Phase II/III — ValidMFAMethods should reject them in Phase I.
        $user = new User();
        $this->expectException(ValidationException::class);
        $user->enable2FA('sms_otp');
    }

    public function testDisable2FA(): void
    {
        $user = new User();
        $this->setEmailVerified($user, true);
        $user->enable2FA('email_otp');
        $this->assertTrue($user->isTwoFactorEnabled());
        $this->assertSame('email_otp', $user->getTwoFactorMethod());
        $user->disable2FA();
        $this->assertFalse($user->isTwoFactorEnabled());
        $this->assertNull($user->getTwoFactorEnforcedAt());
        $this->assertSame('email_otp', $user->getTwoFactorMethod());
    }

    public function testGetAvailableTwoFactorMethods_emailVerified(): void
    {
        $user = new User();
        $this->setEmailVerified($user, true);

        $this->assertSame(['email_otp'], $user->getAvailableTwoFactorMethods());
    }

    public function testGetAvailableTwoFactorMethods_emailNotVerified(): void
    {
        $user = new User();
        $this->setEmailVerified($user, false);

        $this->assertSame([], $user->getAvailableTwoFactorMethods());
    }

    public function testIsTwoFactorMethodEnable(): void
    {
        $user = new User();
        $this->setEmailVerified($user, true);

        $this->assertTrue($user->isTwoFactorMethodEnabled('email_otp'));
        $this->assertFalse($user->isTwoFactorMethodEnabled('sms_otp'));
        $this->assertFalse($user->isTwoFactorMethodEnabled('totp'));
        $this->assertFalse($user->isTwoFactorMethodEnabled('passkey'));
        $this->assertFalse($user->isTwoFactorMethodEnabled('garbage'));
    }

    public function testShouldRequire2FA_configDrivenEnforcement(): void
    {
        // Users in enforced_groups must require 2FA even if two_factor_enabled=false
        // and even if isAdmin() returns false for their group (OAuth2/OpenId admins).
        $groupsUnderTest = [
            IGroupSlugs::OAuth2ServerAdminGroup,
            IGroupSlugs::OpenIdServerAdminsGroup,
        ];

        foreach ($groupsUnderTest as $groupSlug) {
            $user = new User();
            $this->assignGroups($user, [$this->buildGroup($groupSlug)]);
            $this->assertFalse($user->isTwoFactorEnabled());
            $this->assertFalse($user->isAdmin(), "$groupSlug must NOT be covered by isAdmin()");
            $this->assertTrue($user->shouldRequire2FA(), "$groupSlug is in enforced_groups — shouldRequire2FA() must return true regardless of the stored flag");
        }

        // Sanity: a regular user with two_factor_enabled=false is NOT enforced
        $regular = new User();
        $this->assignGroups($regular, [$this->buildGroup(IGroupSlugs::RawUsersGroup)]);
        $this->assertFalse($regular->shouldRequire2FA());
    }

    public function testShouldRequire2FA_emptyEnforcedGroups_fallsThroughToFlag(): void
    {
        // Locks in the config fall-through: when enforced_groups is empty,
        // shouldRequire2FA() must mirror the stored two_factor_enabled flag.
        Config::set('two_factor.enforced_groups', []);

        $user = new User();
        $this->assignGroups($user, [$this->buildGroup(IGroupSlugs::RawUsersGroup)]);
        $user->setTwoFactorEnabled(true);

        $this->assertTrue($user->isTwoFactorEnabled());
        $this->assertTrue($user->shouldRequire2FA());
    }
}
