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
use App\libs\Auth\Models\UserTrustedDevice;
use App\Services\Auth\IDeviceTrustService;
use App\Services\Auth\ITwoFactorAuditService;
use Auth\AuthHelper;
use Auth\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Integration tests for the MFA-gated password login flow wired into UserController.
 *
 * @package Tests
 */
final class TwoFactorLoginFlowTest extends OpenStackIDBaseTestCase
{
    // Seeded super-admin (member of an enforced 2FA group, email verified, email_otp).
    private const ADMIN_EMAIL    = 'sebastian@tipit.net';
    private const SEED_PASSWORD  = '1Qaz2wsx!';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        Session::start();
        $this->flushRateLimitCounters();
    }

    protected function tearDown(): void
    {
        $this->flushRateLimitCounters();
        parent::tearDown();
    }

    private function flushRateLimitCounters(): void
    {
        $admin = EntityManager::getRepository(User::class)->getByEmailOrName(self::ADMIN_EMAIL);
        if (!$admin) return;
        $userId = $admin->getId();
        foreach (['verify', 'recovery', 'resend'] as $action) {
            Cache::forget("2fa_rate:{$action}:{$userId}");
        }
    }

    // -------------------------------------------------------------------------
    // postLogin gate
    // -------------------------------------------------------------------------

    public function testAdminLoginTriggersMFAChallenge(): void
    {
        $response = $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_required', $payload['error_code']);
        $this->assertFalse(Auth::check(), 'no session must be established when a challenge is required');

        $admin = $this->user(self::ADMIN_EMAIL);
        $this->assertGreaterThan(0, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventChallengeIssued));
    }

    public function testNonAdminWithoutMFALogsInNormally(): void
    {
        $email = $this->createPlainUser();

        $response = $this->postLogin($email, self::SEED_PASSWORD);

        $this->assertResponseStatus(302);
        $this->assertTrue(Auth::check(), 'a non-MFA user must get an authenticated session');
    }

    // -------------------------------------------------------------------------
    // verify2FA
    // -------------------------------------------------------------------------

    public function testSuccessfulOTPVerificationCompletesLogin(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $response = $this->verify($code);

        $this->assertResponseStatus(302);
        $this->assertTrue(Auth::check());

        $admin = $this->user(self::ADMIN_EMAIL);
        $this->assertGreaterThan(0, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventChallengeSucceeded));
    }

    public function testFailedOTPVerificationReturnsErrorAndIncrementsCounter(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $admin  = $this->user(self::ADMIN_EMAIL);
        $userId = $admin->getId();

        $response = $this->verify('000000-wrong');

        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_verification_failed', $payload['error_code']);
        $this->assertFalse(Auth::check());

        $this->assertSame(1, (int) Cache::get('2fa_rate:verify:' . $userId, 0), 'verify counter must increment on failure');
        $this->assertGreaterThan(0, $this->countAudit($userId, TwoFactorAuditLog::EventChallengeFailed));
    }

    public function testSuccessfulVerificationDoesNotIncrementCounter(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $userId = $this->user(self::ADMIN_EMAIL)->getId();
        $code   = $this->latestOtpCode(self::ADMIN_EMAIL);

        $this->verify($code);

        $this->assertSame(0, (int) Cache::get('2fa_rate:verify:' . $userId, 0), 'success must NOT increment the verify counter');
    }

    public function testOTPVerificationRejectsWrongCode(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        // Confirm there is a real OTP issued, then send a wrong value.
        $this->latestOtpCode(self::ADMIN_EMAIL); // asserts an OTP exists
        $wrongCode = 'WRONG-CODE-THAT-DOES-NOT-EXIST';

        $response = $this->verify($wrongCode);

        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_verification_failed', $payload['error_code'],
            'verifyChallenge must load the stored OTP and reject a non-matching value');
        $this->assertFalse(Auth::check());
    }

    public function testOTPCodeRejectsReuseAfterSuccessfulVerification(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        // First use — must succeed.
        $this->verify($code);
        $this->assertTrue(Auth::check(), 'first OTP use must establish a session');

        // Second use — OTP must be redeemed (committed by the AuthService tx).
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $response = $this->verify($code);

        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_verification_failed', $payload['error_code'],
            'a reused OTP must be rejected because the redemption was committed by the AuthService transaction');
    }

    public function testRecoveryCodeRejectsReuseAfterTransactionCommit(): void
    {
        $admin = $this->user(self::ADMIN_EMAIL);
        $plain = 'RECOVERY-REUSE-TX-' . uniqid();
        $this->createRecoveryCode($admin, $plain, false);

        // First use — must succeed.
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $this->recovery($plain);
        $this->assertTrue(Auth::check(), 'first recovery-code use must establish a session');

        // Second use — used_at marking must have been committed by the AuthService tx.
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $response = $this->recovery($plain);

        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_invalid_recovery', $payload['error_code'],
            'recovery code reuse must be rejected because used_at was committed via the AuthService transaction');
    }

    public function testExpiredMFASessionFails(): void
    {
        // No prior postLogin -> no pending state.
        $response = $this->verify('whatever');

        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_session_expired', $payload['error_code']);
    }

    // -------------------------------------------------------------------------
    // trusted device
    // -------------------------------------------------------------------------

    public function testTrustDeviceEnrollmentPersistsRecord(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $admin = $this->user(self::ADMIN_EMAIL);
        $code  = $this->latestOtpCode(self::ADMIN_EMAIL);

        $response = $this->verify($code, true);
        $this->assertResponseStatus(302);

        EntityManager::clear();
        $devices = EntityManager::getRepository(UserTrustedDevice::class)->findBy(['user' => $admin->getId()]);
        $this->assertNotEmpty($devices, 'a trusted-device record must be persisted');
        $this->assertGreaterThan(0, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventDeviceTrusted));
    }

    public function testTrustedDeviceCookieBypassesMFA(): void
    {
        $admin = $this->user(self::ADMIN_EMAIL);

        /** @var IDeviceTrustService $deviceTrust */
        $deviceTrust = App::make(IDeviceTrustService::class);
        $rawToken = $deviceTrust->trustDevice($admin, 'Mozilla/5.0 (test)', '127.0.0.1');

        // The device-trust cookie is excluded from encryption, so it is sent verbatim.
        $response = $this->postLogin(
            self::ADMIN_EMAIL,
            self::SEED_PASSWORD,
            [Config::get('two_factor.cookie_name') => $rawToken]
        );

        $this->assertResponseStatus(302);
        $this->assertTrue(Auth::check(), 'a valid trusted-device cookie must bypass MFA');
    }

    // -------------------------------------------------------------------------
    // post-verify transaction boundary (Task 5: device-trust atomic, audit best-effort)
    // -------------------------------------------------------------------------

    public function testAuditFailureDoesNotBlockLogin(): void
    {
        // Audit is best-effort: a failure emitting challenge_succeeded must NOT
        // 500 a user whose OTP is already redeemed and session established.
        $auditMock = \Mockery::mock(ITwoFactorAuditService::class);
        $auditMock->shouldReceive('log')
            ->andReturnUsing(function (User $user, string $eventType) {
                // Allow challenge_issued (postLogin) so the challenge is created;
                // blow up only on the post-success event.
                if ($eventType === TwoFactorAuditLog::EventChallengeSucceeded) {
                    throw new \Exception('audit sink unavailable');
                }
            });
        $this->app->instance(ITwoFactorAuditService::class, $auditMock);

        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $response = $this->verify($code);

        $this->assertEquals(302, $response->getStatusCode(), 'a best-effort audit failure must not fail the login');
        $this->assertTrue(Auth::check(), 'session must be established despite the audit failure');
    }

    public function testDeviceTrustFailureDoesNotBlockLogin(): void
    {
        // Device-trust enrollment is best-effort: by the time it runs the OTP is
        // already redeemed and the session established, so a failure must NOT 500
        // the user (which would lock them out on retry against a now-burned OTP),
        // and the pending MFA state must still be cleared.
        $deviceTrustMock = \Mockery::mock(IDeviceTrustService::class);
        // Gate path: no cookie -> not trusted, so the challenge is still issued.
        $deviceTrustMock->shouldReceive('isDeviceTrusted')->andReturn(false);
        // Enrollment blows up AFTER the OTP has been redeemed and the session set.
        $deviceTrustMock->shouldReceive('trustDevice')
            ->andThrow(new \Exception('trusted-device store unavailable'));
        $this->app->instance(IDeviceTrustService::class, $deviceTrustMock);

        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $response = $this->verify($code, true); // trust_device = true

        $this->assertEquals(302, $response->getStatusCode(), 'a best-effort device-trust failure must not fail the login');
        $this->assertTrue(Auth::check(), 'session must be established despite the device-trust failure');
        $this->assertNull(Session::get('2fa_pending_user_id'), 'pending MFA state must be cleared even when device-trust enrollment fails');
    }

    // -------------------------------------------------------------------------
    // recovery codes
    // -------------------------------------------------------------------------

    public function testRecoveryCodeLoginSucceeds(): void
    {
        $admin = $this->user(self::ADMIN_EMAIL);
        $plain = 'RECOVERY-PLAIN-123';
        $codeId = $this->createRecoveryCode($admin, $plain, false);

        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $response = $this->recovery($plain);

        $this->assertResponseStatus(302);
        $this->assertTrue(Auth::check());

        EntityManager::clear();
        $code = EntityManager::find(UserRecoveryCode::class, $codeId);
        $this->assertTrue($code->isUsed(), 'the recovery code must be marked used');
        $this->assertGreaterThan(0, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventRecoveryUsed));
    }

    public function testUsedRecoveryCodeFails(): void
    {
        $admin = $this->user(self::ADMIN_EMAIL);
        $plain = 'RECOVERY-USED-456';
        $this->createRecoveryCode($admin, $plain, true); // already used

        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $response = $this->recovery($plain);

        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_invalid_recovery', $payload['error_code']);
        $this->assertFalse(Auth::check());
    }

    // -------------------------------------------------------------------------
    // resend
    // -------------------------------------------------------------------------

    public function testResendEndpointReturnsChallengePayload(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $response = $this->resend();

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('otp_length', $payload);
        $this->assertArrayHasKey('otp_lifetime', $payload);
    }

    // -------------------------------------------------------------------------
    // rate limiting
    // -------------------------------------------------------------------------

    public function testVerifyRateLimitBlocksAfterThreshold(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $max = (int) Config::get('two_factor.rate_limit.max_attempts');
        for ($i = 0; $i < $max; $i++) {
            $this->verify('bad-code-' . $i);
        }

        $response = $this->verify('bad-code-final');
        $this->assertResponseStatus(429);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_rate_limit', $payload['error_code']);
    }

    public function testRecoveryRateLimitBlocksAfterThreshold(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $max = (int) Config::get('two_factor.rate_limit.max_attempts');
        for ($i = 0; $i < $max; $i++) {
            $this->recovery('bad-recovery-' . $i);
        }

        $response = $this->recovery('bad-recovery-final');
        $this->assertResponseStatus(429);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_rate_limit', $payload['error_code']);
    }

    public function testResendRateLimitBlocksAfterThreshold(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $max = (int) Config::get('two_factor.rate_limit.max_otp_requests');
        for ($i = 0; $i < $max; $i++) {
            $this->resend();
        }

        $response = $this->resend();
        $this->assertResponseStatus(429);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_rate_limit', $payload['error_code']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function postLogin(string $username, string $password, array $cookies = [])
    {
        return $this->action('POST', 'UserController@postLogin', [
            'username' => $username,
            'password' => $password,
            'flow'     => 'password',
            '_token'   => Session::token(),
        ], [], $cookies);
    }

    private function verify(string $otp, bool $trustDevice = false)
    {
        return $this->action('POST', 'UserController@verify2FA', [
            'otp_value'    => $otp,
            'method'       => User::MFAMethod_OTP,
            'trust_device' => $trustDevice ? '1' : '0',
            '_token'       => Session::token(),
        ]);
    }

    private function recovery(string $code)
    {
        return $this->action('POST', 'UserController@verify2FARecovery', [
            'recovery_code' => $code,
            '_token'        => Session::token(),
        ]);
    }

    private function resend()
    {
        return $this->action('POST', 'UserController@resend2FA', [
            'method' => User::MFAMethod_OTP,
            '_token' => Session::token(),
        ]);
    }

    private function user(string $email): User
    {
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getByEmailOrName($email);
        $this->assertInstanceOf(User::class, $user, "user {$email} not found");
        return $user;
    }

    private function createPlainUser(): string
    {
        $email = 'plain.' . uniqid() . '@test.invalid';
        $user = UserFactory::build([
            'first_name'     => 'Plain',
            'last_name'      => 'User',
            'email'          => $email,
            'password'       => self::SEED_PASSWORD,
            'password_enc'   => AuthHelper::AlgSHA1_V2_4,
            'active'         => true,
            'email_verified' => true,
            'identifier'     => 'plain.' . uniqid(),
        ]);
        EntityManager::persist($user);
        EntityManager::flush();
        return $email;
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

    private function latestOtpCode(string $email): string
    {
        EntityManager::clear();
        /** @var IOAuth2OTPRepository $repo */
        $repo = App::make(IOAuth2OTPRepository::class);
        $otps = $repo->getByUserNameNotRedeemed($email);
        $this->assertNotEmpty($otps, "no OTP issued for {$email}");
        return end($otps)->getValue();
    }

    private function countAudit(int $userId, string $eventType): int
    {
        EntityManager::clear();
        return (int) count(
            EntityManager::getRepository(TwoFactorAuditLog::class)
                ->findBy(['user' => $userId, 'event_type' => $eventType])
        );
    }
}
