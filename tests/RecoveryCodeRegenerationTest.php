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
use Auth\AuthHelper;
use Auth\MFAConstants;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
use App\Services\Auth\IRecoveryCodeService;
use Strategies\MFA\MFAChallengeStrategyFactory;
use Utils\Services\IAuthService;

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
        // A non-enforced user: group-enforced admins are rejected (see
        // testEnableTwoFactorRejectsGroupEnforcedAdmin).
        $user = $this->createPlainUser();
        $this->be($user);

        $response = $this->enableTwoFactor('email_otp');

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('recovery_codes', $payload);
        $this->assertIsInt($payload['batch_id']);
        $this->assertFalse($payload['recovery_codes_acknowledged']);
        $this->assertTrue($payload['recovery_codes_enrollment_required']);

        $expectedCount = (int)config('auth.recovery_codes.count', 10);
        $this->assertCount($expectedCount, $payload['recovery_codes']);
        $this->assertSame($expectedCount, $payload['recovery_codes_remaining']);

        EntityManager::clear();
        $reloaded = EntityManager::getRepository(User::class)->find($user->getId());
        $this->assertTrue($reloaded->isTwoFactorEnabled(), '2FA must be enabled on the user after enrollment');

        $remaining = EntityManager::getRepository(UserRecoveryCode::class)
            ->findBy(['user' => $user->getId(), 'used_at' => null]);
        $this->assertCount($expectedCount, $remaining);
        $this->assertSame($payload['batch_id'], min(array_map(fn($c) => $c->getId(), $remaining)));
    }

    public function testEnableTwoFactorRejectsGroupEnforcedAdmin(): void
    {
        $admin = $this->admin();

        $this->enableTwoFactor('email_otp');

        $this->assertResponseStatus(412);

        EntityManager::clear();
        $reloaded = EntityManager::getRepository(User::class)->find($admin->getId());
        $this->assertFalse($reloaded->isTwoFactorEnabled(), 'the stored flag must stay untouched for an enforced admin');
        $this->assertCount(
            0,
            EntityManager::getRepository(UserRecoveryCode::class)->findBy(['user' => $admin->getId()]),
            'no codes may be minted for an enforced admin'
        );
    }

    public function testDisplayedRecoveryCodeRedeemsThroughVerifyRecoveryCode(): void
    {
        $admin = $this->admin();

        $response = $this->regenerate(self::SEED_PASSWORD);
        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $displayedCode = $payload['recovery_codes'][0];
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+-[A-Z0-9]+$/', $displayedCode);

        EntityManager::clear();
        $admin = EntityManager::getRepository(User::class)->find($admin->getId());
        $unusedBefore = EntityManager::getRepository(UserRecoveryCode::class)
            ->findBy(['user' => $admin->getId(), 'used_at' => null]);

        // The hash was generated over the dash-less string; redeeming the code
        // exactly as it was displayed (with its "-" separator) must still work.
        // Goes through IAuthService, like the real login flow, because
        // verifyRecoveryCode() takes a PESSIMISTIC_WRITE row lock that requires
        // an open transaction.
        $strategy = MFAChallengeStrategyFactory::create(User::MFAMethod_OTP);
        app(IAuthService::class)->verifyMFARecoveryCode($admin, $strategy, $displayedCode);

        EntityManager::clear();
        $unusedAfter = EntityManager::getRepository(UserRecoveryCode::class)
            ->findBy(['user' => $admin->getId(), 'used_at' => null]);

        $this->assertCount(
            count($unusedBefore) - 1,
            $unusedAfter,
            'the code redeemed exactly as displayed must be consumed exactly once'
        );
    }

    public function testEnableTwoFactorRejectsWhenAlreadyEnabled(): void
    {
        $user = $this->createPlainUser();
        $this->be($user);

        $this->enableTwoFactor('email_otp');
        $this->assertResponseStatus(200);

        $this->enableTwoFactor('email_otp');

        $this->assertResponseStatus(412);

        EntityManager::clear();
        $reloaded = EntityManager::getRepository(User::class)->find($user->getId());
        $this->assertTrue($reloaded->isTwoFactorEnabled(), '2FA must remain enabled after the rejected second call');
    }

    public function testEnableTwoFactorRejectsUnavailableMethod(): void
    {
        // sms_otp is a stub in Phase I (isPhoneNumberVerified() is hardcoded false),
        // so enable2FA() must reject it for a user that is allowed to self-enroll.
        $this->be($this->createPlainUser());

        $this->enableTwoFactor('sms_otp');

        $this->assertResponseStatus(412);
    }

    public function testEnableTwoFactorRequiresMethod(): void
    {
        $response = $this->action('POST', 'Api\\UserApiController@enableTwoFactor', [], [], [], []);

        $this->assertResponseStatus(412);
    }

    public function testEnableTwoFactorLogsEnrollmentAuditEvent(): void
    {
        $user = $this->createPlainUser();
        $this->be($user);

        $response = $this->enableTwoFactor('email_otp');
        $payload = json_decode($response->getContent(), true);

        EntityManager::clear();
        $repo = EntityManager::getRepository(TwoFactorAuditLog::class);
        $entries = $repo->findBy(['user' => $user->getId(), 'event_type' => TwoFactorAuditLog::EventEnrollmentChanged]);
        $this->assertNotEmpty($entries, 'an enrollment_changed audit entry must be recorded');

        $generated = $repo->findBy(['user' => $user->getId(), 'event_type' => TwoFactorAuditLog::EventRecoveryCodesGenerated]);
        $this->assertCount(1, $generated);
        $this->assertSame(['batch_id' => $payload['batch_id']], $generated[0]->getMetadata());
    }

    public function testRegenerateReturnsBatchIdAndResetsAcknowledgment(): void
    {
        $admin = $this->admin();
        $service = app(IRecoveryCodeService::class);
        $first = $service->generateRecoveryCodes($admin);
        $this->assertTrue($service->acknowledgeRecoveryCodes($admin, $first->getBatchId()));

        $response = $this->regenerate(self::SEED_PASSWORD);

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertIsInt($payload['batch_id']);
        $this->assertGreaterThan($first->getBatchId(), $payload['batch_id']);
        $this->assertFalse($payload['recovery_codes_acknowledged'], 'a new batch is unacknowledged until the user confirms it');

        EntityManager::clear();
        $reloaded = EntityManager::getRepository(User::class)->find($admin->getId());
        $this->assertNull($reloaded->getRecoveryCodesAcknowledgedAt());
    }

    public function testRegenerateWithoutPasswordIsRejectedForUserWithPassword(): void
    {
        $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [], [], [], []);

        $this->assertResponseStatus(412);
    }

    public function testRegenerateForUserWithoutPasswordIsAcceptedInsideTheWindow(): void
    {
        $user = $this->createPlainUser(false);
        $this->be($user);
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $user->getId(), 'at' => time() - 30]);

        $response = $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [], [], [], []);

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertCount((int)config('auth.recovery_codes.count', 10), $payload['recovery_codes']);
    }

    public function testRegenerateForUserWithoutPasswordIsRejectedOutsideTheWindow(): void
    {
        $user = $this->createPlainUser(false);
        $this->be($user);
        $window = (int)config('two_factor.recovery_enrollment_window', 600);
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $user->getId(), 'at' => time() - $window - 5]);

        $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [], [], [], []);

        $this->assertResponseStatus(412);
        $this->assertCount(0, EntityManager::getRepository(UserRecoveryCode::class)->findBy(['user' => $user->getId()]));
    }

    public function testRegenerateForUserWithoutPasswordIsRejectedWithAnotherUsersMarker(): void
    {
        $user = $this->createPlainUser(false);
        $this->be($user);
        Session::put(MFAConstants::SESSION_KEY_COMPLETED_AT, ['user_id' => $user->getId() + 1000, 'at' => time()]);

        $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [], [], [], []);

        $this->assertResponseStatus(412);
    }

    public function testRegenerateForUserWithoutPasswordIsRejectedWithoutMarker(): void
    {
        $this->be($this->createPlainUser(false));

        $this->action('POST', 'Api\\UserApiController@regenerateRecoveryCodes', [], [], [], []);

        $this->assertResponseStatus(412);
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

    private function createPlainUser(bool $withPassword = true): User
    {
        $payload = [
            'first_name'     => 'Plain',
            'last_name'      => 'User',
            'email'          => 'plain.' . uniqid() . '@test.invalid',
            'active'         => true,
            'email_verified' => true,
            'identifier'     => 'plain.' . uniqid(),
        ];
        if ($withPassword) {
            $payload['password']     = self::SEED_PASSWORD;
            $payload['password_enc'] = AuthHelper::AlgSHA1_V2_4;
        }
        $user = UserFactory::build($payload);
        EntityManager::persist($user);
        EntityManager::flush();
        return $user;
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
