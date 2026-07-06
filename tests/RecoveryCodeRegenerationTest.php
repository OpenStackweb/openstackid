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
use App\libs\Auth\Models\UserRecoveryCode;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Integration tests for regenerating recovery codes from the user profile
 * (Api\UserApiController@regenerateRecoveryCodes) and for enabling 2FA
 * (Api\UserApiController@enableTwoFactor).
 *
 * @package Tests
 */
final class RecoveryCodeRegenerationTest extends BrowserKitTestCase
{
    private const ADMIN_IDENTIFIER = 'sebastian.marcet';
    private const SEED_PASSWORD = '1Qaz2wsx!';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->be($this->admin());
        Session::start();
    }

    public function testRegenerateWithCorrectPasswordInvalidatesOldCodesAndReturnsNewOnes(): void
    {
        $admin = $this->admin();
        $this->createRecoveryCode($admin, 'OLD-CODE-' . uniqid(), false);

        $response = $this->regenerate(self::SEED_PASSWORD);

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('recovery_codes', $payload);

        $expectedCount = (int)config('auth.recovery_codes.count', 10);
        $this->assertCount($expectedCount, $payload['recovery_codes']);
        foreach ($payload['recovery_codes'] as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]+-[A-Z0-9]+$/', $code);
        }

        EntityManager::clear();
        $remaining = EntityManager::getRepository(UserRecoveryCode::class)
            ->findBy(['user' => $admin->getId(), 'used_at' => null]);
        $this->assertCount(
            $expectedCount,
            $remaining,
            'old codes must be invalidated and replaced by exactly the configured count'
        );
    }

    public function testRegenerateWithWrongPasswordFailsAndDoesNotTouchExistingCodes(): void
    {
        $admin = $this->admin();
        $plain = 'KEEP-ME-' . uniqid();
        $this->createRecoveryCode($admin, $plain, false);

        $response = $this->regenerate('this-is-not-the-password');

        $this->assertResponseStatus(412);

        EntityManager::clear();
        $remaining = EntityManager::getRepository(UserRecoveryCode::class)
            ->findBy(['user' => $admin->getId(), 'used_at' => null]);
        $this->assertNotEmpty($remaining, 'existing codes must not be touched when the password confirmation fails');
    }

    public function testRegenerateRequiresCurrentPassword(): void
    {
        $response = $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [], [], [], []);

        $this->assertResponseStatus(412);
    }

    public function testRegenerateLogsAuditEvent(): void
    {
        $admin = $this->admin();

        $this->regenerate(self::SEED_PASSWORD);

        EntityManager::clear();
        $entries = EntityManager::getRepository(TwoFactorAuditLog::class)
            ->findBy(['user' => $admin->getId(), 'event_type' => TwoFactorAuditLog::EventRecoveryCodesGenerated]);
        $this->assertNotEmpty($entries, 'a recovery_codes_generated audit entry must be recorded');
    }

    public function testEnableTwoFactorGeneratesRecoveryCodes(): void
    {
        $admin = $this->admin();

        $response = $this->enableTwoFactor('email_otp');

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('recovery_codes', $payload);

        $expectedCount = (int)config('auth.recovery_codes.count', 10);
        $this->assertCount($expectedCount, $payload['recovery_codes']);

        EntityManager::clear();
        $reloaded = EntityManager::getRepository(User::class)->find($admin->getId());
        $this->assertTrue($reloaded->isTwoFactorEnabled(), '2FA must be enabled on the user after enrollment');

        $remaining = EntityManager::getRepository(UserRecoveryCode::class)
            ->findBy(['user' => $admin->getId(), 'used_at' => null]);
        $this->assertCount($expectedCount, $remaining);
    }

    public function testEnableTwoFactorRejectsUnavailableMethod(): void
    {
        // sms_otp is a stub in Phase I (isPhoneNumberVerified() is hardcoded false),
        // so enable2FA() must reject it regardless of the requesting user.
        $response = $this->enableTwoFactor('sms_otp');

        $this->assertResponseStatus(412);
    }

    public function testEnableTwoFactorRequiresMethod(): void
    {
        $response = $this->action('POST', 'Api\\UserApiController@enableTwoFactor', [], [], [], []);

        $this->assertResponseStatus(412);
    }

    public function testEnableTwoFactorLogsEnrollmentAuditEvent(): void
    {
        $admin = $this->admin();

        $this->enableTwoFactor('email_otp');

        EntityManager::clear();
        $entries = EntityManager::getRepository(TwoFactorAuditLog::class)
            ->findBy(['user' => $admin->getId(), 'event_type' => TwoFactorAuditLog::EventEnrollmentChanged]);
        $this->assertNotEmpty($entries, 'an enrollment_changed audit entry must be recorded');
    }

    private function enableTwoFactor(string $method)
    {
        return $this->action('POST', 'Api\\UserApiController@enableTwoFactor', [
            'method' => $method,
        ], [], [], []);
    }

    private function admin(): User
    {
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => self::ADMIN_IDENTIFIER]);
        $this->assertInstanceOf(User::class, $user, 'seeded admin user not found');
        return $user;
    }

    private function regenerate(string $password)
    {
        return $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [
            'current_password' => $password,
        ], [], [], []);
    }

    private function createRecoveryCode(User $user, string $plain, bool $used): int
    {
        $code = new UserRecoveryCode();
        $code->setUser($user);
        $code->setCodeHash(Hash::make($plain));
        if ($used) {
            $code->markUsed();
        }
        EntityManager::persist($code);
        EntityManager::flush();
        return $code->getId();
    }
}
