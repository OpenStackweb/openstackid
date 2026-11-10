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
use App\libs\Auth\Models\TwoFactorAuditLog;
use App\libs\Auth\Models\UserRecoveryCode;
use App\ModelSerializers\SerializerRegistry;
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\IRecoveryCodeService;
use Auth\AuthHelper;
use Auth\MFAConstants;
use Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * POST /api/v1/users/me/2fa/disable (Api\UserApiController@disableTwoFactor)
 * and the 2FA fields of the user serializers.
 *
 * @package Tests
 */
final class DisableTwoFactorEndpointTest extends BrowserKitTestCase
{
    private const SEED_PASSWORD = '1Qaz2wsx!';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        Session::start();
    }

    private function enrolledUser(bool $withPassword = true): User
    {
        $payload = [
            'first_name'     => 'Disable',
            'last_name'      => 'Endpoint',
            'email'          => 'disable.' . uniqid() . '@test.invalid',
            'active'         => true,
            'email_verified' => true,
            'identifier'     => 'disable.' . uniqid(),
        ];
        if ($withPassword) {
            $payload['password']     = self::SEED_PASSWORD;
            $payload['password_enc'] = AuthHelper::AlgSHA1_V2_4;
        }
        $user = UserFactory::build($payload);
        EntityManager::persist($user);
        EntityManager::flush();

        $this->app->make(IRecoveryCodeService::class)
            ->enableTwoFactorAndGenerateCodes($user, User::MFAMethod_OTP);
        $this->app->make(IDeviceTrustService::class)->trustDevice($user, 'agent-a', '127.0.0.1');
        $this->be($user);
        return $user;
    }

    private function disable(?string $password)
    {
        return $this->action('POST', 'Api\\UserApiController@disableTwoFactor',
            is_null($password) ? [] : ['current_password' => $password], [], [], []);
    }

    private function row(User $user): object
    {
        return DB::table('users')->where('id', $user->getId())->first();
    }

    private function assertStillEnrolled(User $user): void
    {
        $this->assertSame(1, (int)$this->row($user)->two_factor_enabled);
        $this->assertSame(10, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
        $this->assertSame(0, DB::table('user_trusted_devices')
            ->where('user_id', $user->getId())->where('is_revoked', 1)->count());
    }

    public function testDisableWithCorrectPasswordClearsEverything(): void
    {
        $user = $this->enrolledUser();

        $response = $this->disable(self::SEED_PASSWORD);

        $this->assertResponseStatus(200);
        $this->assertSame(['two_factor_enabled' => false], json_decode($response->getContent(), true));

        $row = $this->row($user);
        $this->assertSame(0, (int)$row->two_factor_enabled);
        $this->assertNull($row->two_factor_enforced_at);
        $this->assertSame(0, DB::table('user_recovery_codes')->where('user_id', $user->getId())->count());
        $devices = DB::table('user_trusted_devices')->where('user_id', $user->getId())->get();
        $this->assertNotEmpty($devices);
        foreach ($devices as $device) {
            $this->assertSame(1, (int)$device->is_revoked);
        }

        $audit = DB::table('two_factor_audit_log')
            ->where('user_id', $user->getId())
            ->where('event_type', TwoFactorAuditLog::EventEnrollmentChanged)
            ->orderByDesc('id')->first();
        $metadata = json_decode($audit->metadata, true);
        $this->assertFalse($metadata['enabled']);
        $this->assertSame($user->getId(), $metadata['actor_id']);
        $this->assertSame('self', $metadata['reason']);
    }

    public function testDisableExpiresTheDeviceTrustCookie(): void
    {
        $this->enrolledUser();

        $this->disable(self::SEED_PASSWORD);

        $this->assertResponseStatus(200);
        $name = config('two_factor.cookie_name', 'device_trust_token');
        $queued = array_filter(Cookie::getQueuedCookies(), fn($c) => $c->getName() === $name);
        $this->assertCount(1, $queued);
        $this->assertLessThan(time(), array_values($queued)[0]->getExpiresTime());
    }

    public function testDisableWithWrongPasswordChangesNothing(): void
    {
        $user = $this->enrolledUser();

        $this->disable('not-the-password');

        $this->assertResponseStatus(412);
        $this->assertStillEnrolled($user);
    }

    public function testDisableWithoutPasswordIsRejectedForUserWithPassword(): void
    {
        $user = $this->enrolledUser();

        $this->disable(null);

        $this->assertResponseStatus(412);
        $this->assertStillEnrolled($user);
    }

    public function testDisableUnauthenticatedReturns403(): void
    {
        $this->enrolledUser();
        Auth::logout();

        $this->disable(self::SEED_PASSWORD);

        $this->assertResponseStatus(403);
    }

    public function testDisableWhenTwoFactorIsNotEnabledReturns412(): void
    {
        $user = $this->enrolledUser();
        $this->disable(self::SEED_PASSWORD);
        $this->assertResponseStatus(200);

        $this->disable(self::SEED_PASSWORD);

        $this->assertResponseStatus(412);
    }

    public function testDisableForGroupEnforcedUserReturns412AndChangesNothing(): void
    {
        $admin = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->assertTrue($admin->isTwoFactorEnforcedByGroup(), 'seeded admin must be in an enforced group');
        $this->app->make(IRecoveryCodeService::class)->generateRecoveryCodes($admin);
        $before = DB::table('user_recovery_codes')->where('user_id', $admin->getId())->count();
        $this->be($admin);

        $this->disable(self::SEED_PASSWORD);

        $this->assertResponseStatus(412);
        $this->assertSame($before, DB::table('user_recovery_codes')->where('user_id', $admin->getId())->count());
        EntityManager::clear();
        $this->assertTrue(EntityManager::getRepository(User::class)->find($admin->getId())->shouldRequire2FA());
    }

    public function testDisableForUserWithoutPasswordInsideTheWindow(): void
    {
        $user = $this->enrolledUser(false);
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $user->getId(), 'at' => time() - 30]);

        $this->disable(null);

        $this->assertResponseStatus(200);
        $this->assertSame(0, (int)$this->row($user)->two_factor_enabled);
    }

    public function testDisableForUserWithoutPasswordOutsideTheWindow(): void
    {
        $user = $this->enrolledUser(false);
        $window = (int)config('two_factor.recovery_enrollment_window', 600);
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $user->getId(), 'at' => time() - $window - 5]);

        $this->disable(null);

        $this->assertResponseStatus(412);
        $this->assertStillEnrolled($user);
    }

    public function testAdminSerializerExposesTheTwoFactorFieldsAndPrivateAndPublicDoNot(): void
    {
        $user = $this->enrolledUser();
        $keys = ['two_factor_enabled', 'two_factor_method', 'two_factor_enforced', 'two_factor_enforced_at'];
        $registry = SerializerRegistry::getInstance();

        $admin = $registry->getSerializer($user, SerializerRegistry::SerializerType_Admin)->serialize();
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $admin);
        }
        $this->assertTrue($admin['two_factor_enabled']);
        $this->assertSame(User::MFAMethod_OTP, $admin['two_factor_method']);
        $this->assertFalse($admin['two_factor_enforced']);
        $this->assertNotNull($admin['two_factor_enforced_at']);
        // the admin serializer is the private one plus the 2FA state
        $this->assertSame([], array_diff(
            array_keys($registry->getSerializer($user, SerializerRegistry::SerializerType_Private)->serialize()),
            array_keys($admin)
        ));

        // OAuth2 clients get the private serializer, public is public: neither has the 2FA state
        foreach ([SerializerRegistry::SerializerType_Private, SerializerRegistry::SerializerType_Public] as $type) {
            $payload = $registry->getSerializer($user, $type)->serialize();
            foreach ($keys as $key) {
                $this->assertArrayNotHasKey($key, $payload, "$type must not expose $key");
            }
        }
    }
}
