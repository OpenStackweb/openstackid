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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use Auth\Repositories\IUserRecoveryCodeRepository;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;
use Services\OAuth2\PrincipalService;
use Strategies\ILoginStrategy;
use Strategies\MFA\IMFAChallengeStrategy;
use Strategies\MFA\MFAChallengeStrategyFactory;
use Utils\Services\IAuthService;

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
        if ($admin) {
            $userId = $admin->getId();
            foreach (['verify', 'recovery', 'resend'] as $action) {
                Cache::forget("2fa_rate:{$action}:{$userId}");
            }
        }

        // otp is keyed by the (lowercased) submitted email, not a user id -
        // clear every literal email this test class submits to that action.
        foreach ([self::ADMIN_EMAIL, 'someone-else@example.com'] as $email) {
            Cache::forget('2fa_rate:otp:' . strtolower($email));
        }
    }

    // -------------------------------------------------------------------------
    // postLogin gate
    // -------------------------------------------------------------------------

    public function testAdminLoginTriggersMFAChallenge(): void
    {
        $response = $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        // The password flow submits as a native form POST, so the challenge
        // is delivered via the same redirect+session-flash mechanism as every
        // other login outcome (errorLogin()), not a live JSON response -
        // see testAdminLoginPersistsUIStateForRefreshResilience for the
        // session-state assertions the redirected page relies on.
        $this->assertResponseStatus(302, 'must redirect back to the login screen, same as errorLogin(), not return JSON');
        $this->assertFalse(Auth::check(), 'no session must be established when a challenge is required');

        $admin = $this->user(self::ADMIN_EMAIL);
        $this->assertGreaterThan(0, $this->countAudit($admin->getId(), TwoFactorAuditLog::EventChallengeIssued));
    }

    public function testAdminLoginPersistsUIStateForRefreshResilience(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $this->assertSame('2fa', Session::get('flow'), 'a refresh mid-challenge must restore the 2FA screen, not the password form');
        $this->assertNotNull(Session::get('otp_length'));
        $this->assertNotNull(Session::get('otp_lifetime'));
        $this->assertNotNull(Session::get('otp_issued_at'), 'the issuance timestamp must be restorable so a refresh can seed the countdown with the REMAINING lifetime');
        $this->assertSame(User::MFAMethod_OTP, Session::get('mfa_method'), 'a refresh must restore the screen for the method actually challenged, not a hardcoded default');
        $this->assertSame(ILoginStrategy::MFA_REQUIRED, Session::get('error_code'), 'must match what DisplayResponseJsonStrategy sends native clients in its JSON body');
    }

    public function testRefreshMidChallengeSeedsCountdownWithRemainingLifetime(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $lifetime = intval(Session::get('otp_lifetime'));
        $this->assertGreaterThan(0, $lifetime);

        // Simulate a refresh 100 seconds into the challenge.
        Session::put('otp_issued_at', time() - 100);

        $response = $this->action('GET', 'UserController@getLogin');
        $this->assertResponseOk();

        $this->assertSame(
            1,
            preg_match('/config\.otpLifetime = (\d+);/', $response->getContent(), $matches),
            'the login page must seed the countdown from session state'
        );
        $remaining = intval($matches[1]);
        // The countdown must be seeded with the REMAINING lifetime (~lifetime - 100),
        // not restart at the full TTL - otherwise the UI overstates how long the
        // code is valid and lets the user burn rate-limited attempts on a
        // server-expired code. +/-2s tolerance for clock ticks between requests.
        $this->assertLessThanOrEqual($lifetime - 98, $remaining);
        $this->assertGreaterThanOrEqual($lifetime - 102, $remaining);
    }

    public function testSuccessfulVerificationClearsUIState(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $this->verify($code);

        $this->assertNull(Session::get('flow'), 'completed challenge must not leave the 2FA screen re-derivable from a stale refresh');
        $this->assertNull(Session::get('otp_length'));
        $this->assertNull(Session::get('otp_lifetime'));
        $this->assertNull(Session::get('otp_issued_at'));
        $this->assertNull(Session::get('mfa_method'));
        $this->assertNull(Session::get('error_code'));
        // Identity/display fields written by postLogin()'s challengeRequired()
        // payload must not survive a completed login either - otherwise a
        // later visitor on the same browser session inherits this identity.
        $this->assertNull(Session::get('username'));
        $this->assertNull(Session::get('user_fullname'));
        $this->assertNull(Session::get('user_pic'));
        $this->assertNull(Session::get('user_verified'));
        $this->assertNull(Session::get('user_is_active'));
    }

    public function testNonAdminWithoutMFALogsInNormally(): void
    {
        $email = $this->createPlainUser();

        $response = $this->postLogin($email, self::SEED_PASSWORD);

        $this->assertResponseStatus(302);
        $this->assertTrue(Auth::check(), 'a non-MFA user must get an authenticated session');
    }

    // -------------------------------------------------------------------------
    // passwordless (flow=otp) login must not bypass the MFA gate
    // -------------------------------------------------------------------------

    public function testEnforcedUserCannotBypassMFAViaPasswordlessLogin(): void
    {
        $this->emitOTP(self::ADMIN_EMAIL);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $response = $this->postLoginOTP(self::ADMIN_EMAIL, $code);

        $this->assertFalse(Auth::check(), 'passwordless login must not authenticate an enforced-2FA user');

        // The OTP flow still submits as a native form POST (PR #142 only
        // converted the password flow to AJAX), so the rejection must go
        // through the pre-existing errorLogin() redirect+flash mechanism,
        // not the JSON contract built for the password flow's MFA gate.
        $this->assertResponseStatus(302, 'must reuse errorLogin(), not a JSON response');
        $this->assertStringContainsString(
            'two-factor authentication',
            Session::get('flash_notice'),
            'the flashed message must explain why passwordless login was rejected'
        );
        $this->assertSame('otp', Session::get('flow'), 'a reload must land back on the OTP screen, not silently fall back to password');
    }

    public function testNonEnforcedUserStillLogsInViaPasswordlessLogin(): void
    {
        $email = $this->createPlainUser();
        $this->emitOTP($email);
        $code = $this->latestOtpCode($email);

        $this->postLoginOTP($email, $code);

        $this->assertTrue(Auth::check(), 'passwordless login must keep working unchanged for non-enforced users');
    }

    public function testEnforcedUserCanUsePasswordlessWhenTwoFactorGloballyDisabled(): void
    {
        // Kill-switch (SDS idp-mfa.md §10.1): with 2FA globally disabled, the
        // passwordless-login enforcement block must NOT fire - an enforced admin
        // can log in passwordless again, matching "revert to password-only login".
        // Regression guard for the gap where the block called shouldRequire2FA()
        // without honoring config('two_factor.enabled').
        Config::set('two_factor.enabled', false);

        $this->emitOTP(self::ADMIN_EMAIL);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $this->postLoginOTP(self::ADMIN_EMAIL, $code);

        $this->assertTrue(
            Auth::check(),
            'with the kill-switch off, passwordless login must not be blocked for an enforced admin'
        );
    }

    // -------------------------------------------------------------------------
    // cancelLogin
    // -------------------------------------------------------------------------

    public function testCancelClearsUIStateAndPendingChallenge(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $this->cancelLogin();

        $this->assertNull(Session::get('flow'), 'cancel must not leave a stale 2FA screen restorable on refresh');
        $this->assertNull(Session::get('mfa_method'));
        $this->assertNull(Session::get('otp_length'));
        $this->assertNull(Session::get('otp_lifetime'));
        $this->assertNull(Session::get('otp_issued_at'));
        // Identity/display fields written by postLogin()'s challengeRequired()
        // payload must not survive cancel either - otherwise a later visitor
        // on the same browser session inherits the cancelled attempt's identity.
        $this->assertNull(Session::get('username'));
        $this->assertNull(Session::get('user_fullname'));
        $this->assertNull(Session::get('user_pic'));
        $this->assertNull(Session::get('user_verified'));
        $this->assertNull(Session::get('user_is_active'));

        // The strongest proof: the OTP issued before cancel must no longer
        // complete a login. If pending state survived cancel, this would
        // succeed with a 302 despite the user having explicitly cancelled.
        $response = $this->verify($code);
        $this->assertResponseStatus(401);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_session_expired', $payload['error_code']);
        $this->assertFalse(Auth::check(), 'a cancelled challenge must never establish a session');
    }

    // -------------------------------------------------------------------------
    // verify2FA
    // -------------------------------------------------------------------------

    public function testSuccessfulOTPVerificationCompletesLogin(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        $response = $this->verify($code);

        // verify2FA returns the post-login destination as JSON data (not a raw redirect)
        // so the caller's XHR never has to follow it itself - a real top-level navigation
        // to redirect_url is what actually completes any further hop, cross-origin included.
        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertIsString($payload['redirect_url'] ?? null);
        $this->assertStringStartsWith('http', $payload['redirect_url']);
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

    public function testOTPRedeemRollsBackOnMidTransactionFailure(): void
    {
        // Ticket CU-86ba2zc6p TESTS list: "OTP redeem is persisted only on
        // commit; a failure inside the verify transaction rolls back the
        // redeem." Wraps the REAL strategy so the OTP genuinely gets redeemed
        // mid-transaction, then injects a failure before the transaction
        // (AuthService::verifyMFAChallenge) can commit.
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);
        $admin = $this->user(self::ADMIN_EMAIL);

        $realStrategy = MFAChallengeStrategyFactory::create(User::MFAMethod_OTP);
        $faultyStrategy = new class($realStrategy) implements IMFAChallengeStrategy {
            public function __construct(private IMFAChallengeStrategy $inner)
            {
            }

            public function issueChallenge(User $user, ?Client $client, bool $remember): array
            {
                return $this->inner->issueChallenge($user, $client, $remember);
            }

            public function verifyChallenge(User $user, string $code, ?Client $client = null): void
            {
                $this->inner->verifyChallenge($user, $code, $client);
                throw new \RuntimeException('Simulated mid-transaction failure after redeem');
            }

            public function resendChallenge(User $user, ?Client $client, bool $remember): array
            {
                return $this->inner->resendChallenge($user, $client, $remember);
            }

            public function getPendingState(): ?array
            {
                return $this->inner->getPendingState();
            }

            public function clearPendingState(): void
            {
                $this->inner->clearPendingState();
            }

            public function verifyRecoveryCode(User $user, string $code): void
            {
                $this->inner->verifyRecoveryCode($user, $code);
            }
        };

        /** @var IAuthService $authService */
        $authService = App::make(IAuthService::class);

        try {
            $authService->verifyMFAChallenge($admin, $faultyStrategy, $code);
            $this->fail('Expected the simulated mid-transaction failure to propagate');
        } catch (\RuntimeException $ex) {
            $this->assertSame('Simulated mid-transaction failure after redeem', $ex->getMessage());
        }

        EntityManager::clear();
        /** @var IOAuth2OTPRepository $otpRepo */
        $otpRepo = App::make(IOAuth2OTPRepository::class);
        $otp = $otpRepo->getByValue($code);

        $this->assertNotNull($otp, 'the OTP row itself must still exist - only the redeem must roll back');
        $this->assertFalse(
            $otp->isRedeemed(),
            'a failure inside the verify transaction must roll back the OTP redeem, not persist it'
        );
    }

    // -------------------------------------------------------------------------
    // concurrency: pessimistic-lock proof for OTP / recovery-code redemption
    //
    // refreshExclusiveLock() (EmailOTPMFAChallengeStrategy::verifyChallenge,
    // AbstractMFAChallengeStrategy::verifyRecoveryCode) exists specifically to
    // close a check-then-redeem TOCTOU race between two concurrent requests.
    // testOTPCodeRejectsReuseAfterSuccessfulVerification / testRecoveryCodeRejects
    // ReuseAfterTransactionCommit above only prove SEQUENTIAL reuse is rejected.
    // These tests prove the row lock the production code acquires actually
    // blocks a second, independent physical DB connection while held.
    // -------------------------------------------------------------------------

    public function testOTPRedeemRowLockBlocksConcurrentConnection(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);

        /** @var IOAuth2OTPRepository $otpRepo */
        $otpRepo = App::make(IOAuth2OTPRepository::class);
        $otp = $otpRepo->getByValue($code);
        $this->assertNotNull($otp);

        $this->assertLockBlocksSecondConnection(
            'oauth2_otp',
            $otp->getId(),
            fn() => $otpRepo->refreshExclusiveLock($otp)
        );
    }

    public function testRecoveryCodeRowLockBlocksConcurrentConnection(): void
    {
        $admin = $this->user(self::ADMIN_EMAIL);
        $plain = 'RECOVERY-LOCK-' . uniqid();
        $codeId = $this->createRecoveryCode($admin, $plain, false);

        /** @var IUserRecoveryCodeRepository $recoveryRepo */
        $recoveryRepo = App::make(IUserRecoveryCodeRepository::class);
        $recoveryCode = EntityManager::find(UserRecoveryCode::class, $codeId);
        $this->assertNotNull($recoveryCode);

        $this->assertLockBlocksSecondConnection(
            'user_recovery_codes',
            $codeId,
            fn() => $recoveryRepo->refreshExclusiveLock($recoveryCode)
        );
    }

    /**
     * Proves that a PESSIMISTIC_WRITE lock acquired via $acquireLock (the same
     * production method the MFA strategies call before redeeming) blocks a
     * genuinely separate, concurrent physical DB connection from also locking
     * that row - i.e. the fix actually closes the TOCTOU redemption race, not
     * just rejects a sequential re-submission.
     *
     * $table is always an internal literal supplied by this test file, never
     * external input, so interpolating it into the probe SQL below is safe.
     */
    private function assertLockBlocksSecondConnection(string $table, int $id, \Closure $acquireLock): void
    {
        $primary = EntityManager::getConnection();
        $primary->beginTransaction();

        try {
            $acquireLock();

            // Register a second connection under a distinct name so Laravel's
            // DatabaseManager opens an independent physical connection instead
            // of returning the already-cached primary one.
            Config::set('database.connections.mfa_lock_test_secondary', Config::get('database.connections.openstackid'));
            $secondary = DB::connection('mfa_lock_test_secondary');

            $primaryConnId   = (int) $primary->executeQuery('SELECT CONNECTION_ID()')->fetchOne();
            $secondaryConnId = (int) $secondary->selectOne('SELECT CONNECTION_ID() AS id')->id;
            $this->assertNotSame(
                $primaryConnId,
                $secondaryConnId,
                'test requires two independent physical DB connections to prove real lock contention'
            );

            $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');
            $secondary->beginTransaction();

            try {
                $secondary->selectOne("SELECT id FROM {$table} WHERE id = ? FOR UPDATE", [$id]);
                $this->fail('a second connection must not be able to lock a row already held by refreshExclusiveLock()');
            } catch (\Illuminate\Database\QueryException $ex) {
                $this->assertStringContainsStringIgnoringCase(
                    'lock wait timeout',
                    $ex->getMessage(),
                    'the second connection must be blocked by the row lock, not fail for an unrelated reason'
                );
            } finally {
                $secondary->rollBack();
                DB::purge('mfa_lock_test_secondary');
            }
        } finally {
            $primary->rollBack();
        }
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
    // session security (SDS idp-mfa.md §9.3: session fixation)
    //
    // Session-fixation protection itself is already provided by Laravel's
    // SessionGuard::login() (session->migrate(true), called via Auth::login()
    // inside loginUser()) - not something this branch needs to add. What
    // AuthServiceLoginUserTest and the test below actually cover is the
    // ordering bug this investigation found: PrincipalService::register()
    // must run AFTER Auth::login(), not before, or its op_browser_state hash
    // is computed from a session ID Auth::login() is about to invalidate.
    // -------------------------------------------------------------------------

    public function testCompletedMFALoginKeepsOPBrowserStateConsistentWithSessionId(): void
    {
        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        $code = $this->latestOtpCode(self::ADMIN_EMAIL);
        $this->verify($code);

        // PrincipalService::register() hashes the session ID into
        // op_browser_state (OIDC Session Management). If it ran BEFORE
        // Auth::login()'s internal session->migrate(true), this would be a
        // hash of the id Auth::login() was about to invalidate instead.
        $this->assertSame(
            hash('sha256', Session::getId()),
            Session::get(PrincipalService::OPBrowserState),
            'op_browser_state must be derived from the post-regeneration session ID'
        );
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
        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertIsString($payload['redirect_url'] ?? null);

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

        $this->assertEquals(200, $response->getStatusCode(), 'a best-effort audit failure must not fail the login');
        $this->assertTrue(Auth::check(), 'session must be established despite the audit failure');
    }

    public function testRecoveryAuditFailureDoesNotBlockLogin(): void
    {
        // Audit is best-effort: a failure emitting recovery_used must NOT 500 a
        // user whose recovery code is already burned and session established.
        $admin = $this->user(self::ADMIN_EMAIL);
        $plain = 'RECOVERY-AUDIT-FAIL-' . uniqid();
        $this->createRecoveryCode($admin, $plain, false);

        $auditMock = \Mockery::mock(ITwoFactorAuditService::class);
        $auditMock->shouldReceive('log')
            ->andReturnUsing(function (User $user, string $eventType) {
                // Allow challenge_issued (postLogin) so the challenge is created;
                // blow up only on the post-success event.
                if ($eventType === TwoFactorAuditLog::EventRecoveryUsed) {
                    throw new \Exception('audit sink unavailable');
                }
            });
        $this->app->instance(ITwoFactorAuditService::class, $auditMock);

        $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        $response = $this->recovery($plain);

        $this->assertEquals(200, $response->getStatusCode(), 'a best-effort audit failure must not fail the login');
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

        $this->assertEquals(200, $response->getStatusCode(), 'a best-effort device-trust failure must not fail the login');
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

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertIsString($payload['redirect_url'] ?? null);
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

    public function testInitialChallengeIssuanceCountsAgainstResendRateLimitWindow(): void
    {
        // SDS idp-mfa.md §4.12: "The initial OTP issuance during postLogin()
        // shares the 2fa_rate:resend:{user_id} cache key, ensuring the first
        // challenge counts against the same 5-request issuance window as
        // subsequent resends." Each postLogin() call re-issues a challenge and
        // must count against that SAME window.
        $max = (int) Config::get('two_factor.rate_limit.max_otp_requests');
        for ($i = 0; $i < $max; $i++) {
            $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);
        }

        $response = $this->postLogin(self::ADMIN_EMAIL, self::SEED_PASSWORD);

        // Rate-limited postLogin() must reuse errorLogin(), not the JSON
        // contract resend()/verify() use - the password form still submits
        // as a native form POST.
        $this->assertResponseStatus(302, 'must redirect like every other login outcome, not return JSON');
        $this->assertStringContainsString('Too many attempts', Session::get('flash_notice'));
        $this->assertFalse(Auth::check());
    }

    public function testOtpEmailRateLimitBlocksAfterThreshold(): void
    {
        $max = (int) Config::get('two_factor.rate_limit.max_otp_email_requests');
        for ($i = 0; $i < $max; $i++) {
            $this->emitOTP(self::ADMIN_EMAIL);
        }

        $response = $this->emitOTP(self::ADMIN_EMAIL);
        $this->assertResponseStatus(429);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_rate_limit', $payload['error_code']);

        // A different email must be unaffected - the subject is per-email, not global.
        // emitOTP() never looks up an existing user before creating the OTP, so a
        // non-seeded literal email is a valid, distinct rate-limit subject here.
        $otherResponse = $this->emitOTP('someone-else@example.com');
        $this->assertNotEquals(429, $otherResponse->getStatusCode());
    }

    public function testOtpEmailRateLimitIsCaseInsensitive(): void
    {
        // users.email has a case-insensitive collation (utf8mb3_unicode_ci) and every
        // session-keyed 2FA action resolves through a case-insensitive DB lookup before
        // ever touching the rate limiter. The otp action has no such lookup - the raw
        // submitted string IS the cache key - so casing must be canonicalized here or
        // an attacker can reset the budget every request by cycling the target email's
        // letter casing, defeating the limit entirely.
        $max = (int) Config::get('two_factor.rate_limit.max_otp_email_requests');
        $casings = ['sebastian@tipit.net', 'Sebastian@Tipit.net', 'SEBASTIAN@TIPIT.NET', 'sEbAsTiAn@tIpIt.NeT'];
        for ($i = 0; $i < $max; $i++) {
            $this->emitOTP($casings[$i % count($casings)]);
        }

        $response = $this->emitOTP('SEBASTIAN@TIPIT.NET');
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

    private function cancelLogin()
    {
        return $this->action('POST', 'UserController@cancelLogin', [
            '_token' => Session::token(),
        ]);
    }

    private function emitOTP(string $username)
    {
        return $this->action('POST', 'UserController@emitOTP', [
            'username'   => $username,
            'connection' => 'email',
            'send'       => 'code',
            '_token'     => Session::token(),
        ]);
    }

    private function postLoginOTP(string $username, string $code)
    {
        return $this->action('POST', 'UserController@postLogin', [
            'username'   => $username,
            'password'   => $code,
            'connection' => 'email',
            'flow'       => 'otp',
            '_token'     => Session::token(),
        ]);
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
