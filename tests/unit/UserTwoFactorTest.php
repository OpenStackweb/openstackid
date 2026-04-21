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
        $property   = $reflection->getProperty('groups');
        $property->setAccessible(true);
        $collection = $property->getValue($user);
        foreach ($groups as $group) {
            $collection->add($group);
        }
    }

    private function setEmailVerified(User $user, bool $verified): void
    {
        $reflection = new \ReflectionClass(User::class);
        $property   = $reflection->getProperty('email_verified');
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

    public function testShouldRequire2FA_oauth2AdminUser(): void
    {
        // Guard against the gotcha: shouldRequire2FA() must look at the full
        // config('two_factor.enforced_groups') list, not just call isAdmin().
        $user = new User();
        $this->assignGroups($user, [$this->buildGroup(IGroupSlugs::OAuth2ServerAdminGroup)]);

        $this->assertFalse($user->isAdmin(), 'oauth2-server-admins is NOT an admin group per isAdmin()');
        $this->assertTrue($user->shouldRequire2FA());
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

        $this->assertTrue($user->isTwoFactorMethodEnable('email_otp'));
        $this->assertFalse($user->isTwoFactorMethodEnable('sms_otp'));
        $this->assertFalse($user->isTwoFactorMethodEnable('totp'));
        $this->assertFalse($user->isTwoFactorMethodEnable('passkey'));
        $this->assertFalse($user->isTwoFactorMethodEnable('garbage'));
    }
}
