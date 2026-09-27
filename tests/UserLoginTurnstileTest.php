<?php
namespace Tests;
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
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Session;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

/**
 * Class UserLoginTurnstileTest
 *
 * Covers Cloudflare Turnstile integration in UserController::postLogin():
 *  - cf-turnstile-response required when captcha_failed_attempts (session) >= threshold
 *  - threshold gating (before / at boundary / above boundary)
 *  - absent captcha_failed_attempts session key defaults to zero (no captcha required)
 *  - captcha is gated on the server-side session counter, not request-body input
 *  - login screen emits Turnstile JS config after a failed attempt
 *  - expired or unsolved token is rejected
 */
final class UserLoginTurnstileTest extends BrowserKitTestCase
{
    private const LOGIN_URL = '/auth/login';
    // Matches ServerConfigurationService::DefaultMaxFailedLoginAttempts2ShowCaptcha
    private const CAPTCHA_THRESHOLD = 3;

    private ?string $testEmail = null;
    private ?string $testPassword = null;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        // read into locals first: assigning null to the typed string properties would
        // throw a TypeError before the skip guard below can run
        $testEmail = env('TEST_USER_EMAIL');
        $testPassword = env('TEST_USER_PASSWORD');
        if (empty($testEmail) || empty($testPassword)) {
            $this->markTestSkipped('TEST_USER_EMAIL and TEST_USER_PASSWORD env vars are required.');
        }
        $this->testEmail = $testEmail;
        $this->testPassword = $testPassword;
        Session::start();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function postLogin(array $overrides = [], array $sessionData = [])
    {
        // GET establishes the session and CSRF token, mirroring a real browser.
        $this->call('GET', self::LOGIN_URL);

        // Inject session data after session is established, before the POST reads it.
        foreach ($sessionData as $key => $value) {
            $this->app['session']->driver()->put($key, $value);
        }

        // Persist injected data to the session store so the POST kernel cycle can load it.
        $this->app['session']->driver()->save();

        // Re-send the session cookie so StartSession loads the same session ID on the POST.
        return $this->call('POST', self::LOGIN_URL, array_merge([
            'username' => $this->testEmail,
            'password' => $this->testPassword,
            'flow' => 'password',
            '_token' => Session::token(),
        ], $overrides), $this->makeEncryptedSessionCookie());
    }

    private function fakeTurnstilePass(): void
    {
        Turnstile::fake(); // FakeClient defaults to shouldPass=true → returns SiteverifyResponse::success()
    }

    private function fakeTurnstileFail(): void
    {
        Turnstile::fake()->expired(); // FakeClient returns failure(['timeout-or-duplicate'])
    }

    /**
     * BrowserKitTesting's assertSessionHasErrors/assertSessionMissing target
     * $app['session.store'], which is a fresh Store singleton never populated by
     * the request's StartSession middleware ($app['session']->driver()). Use the
     * live session driver instead.
     */
    private function sessionHasValidationError(string $field): bool
    {
        $errors = $this->app['session']->driver()->get('errors');
        return $errors !== null && $errors->has($field);
    }

    private function makeEncryptedSessionCookie(): array
    {
        $sessionName = $this->app['session']->getName();
        $sessionId = $this->app['session']->driver()->getId();
        $cookie = encrypt(
            CookieValuePrefix::create($sessionName, $this->app['encrypter']->getKey()) . $sessionId,
            false
        );
        return [$sessionName => $cookie];
    }

    // -------------------------------------------------------------------------
    // 1. Validation failure when cf-turnstile-response is missing
    // -------------------------------------------------------------------------

    public function testMissingTurnstileResponseFailsValidationWhenAtThreshold(): void
    {
        $this->postLogin(
            [],  // no cf-turnstile-response
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]
        );

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'Expected a validation error for cf-turnstile-response when session counter is at threshold'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Login flow behaviour before / after CAPTCHA threshold
    // -------------------------------------------------------------------------

    public function testLoginBelowThresholdDoesNotRequireTurnstile(): void
    {
        $this->postLogin(
            [],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD - 1]
        );

        $this->assertFalse(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'Turnstile must not be required when session counter is below threshold'
        );
    }

    public function testLoginAtThresholdWithValidTokenPassesValidation(): void
    {
        $this->fakeTurnstilePass();

        $this->postLogin(
            ['cf-turnstile-response' => 'dummy-token-accepted-by-mock'],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]
        );

        $this->assertFalse(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'A valid Turnstile token must clear the captcha validation rule'
        );
    }

    public function testAbsentSessionCounterDefaultsToZeroNoCaptchaRequired(): void
    {
        // No captcha_failed_attempts in session → defaults to 0 → below threshold.
        $this->postLogin([
            'username' => 'nobody@doesnotexist.example',
            'password' => 'irrelevant',
        ]);

        $this->assertFalse(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'Turnstile must not be required when captcha_failed_attempts is absent from session'
        );
    }

    // -------------------------------------------------------------------------
    // 4. Rendering of Turnstile on the thresholded login screen
    // -------------------------------------------------------------------------

    public function testLoginScreenIncludesTurnstileConfigWhenAboveThreshold(): void
    {
        // Part 1: blade renders counter one below threshold.
        // Establish session, inject, save, then GET with explicit cookie (same pattern as
        // testLoginScreenEmitsLoginAttemptsFromSessionKey).
        $this->call('GET', self::LOGIN_URL);
        $this->app['session']->driver()->put('captcha_failed_attempts', self::CAPTCHA_THRESHOLD - 1);
        $this->app['session']->driver()->save();
        $html = $this->call('GET', self::LOGIN_URL, [], $this->makeEncryptedSessionCookie())->getContent();
        $this->assertStringContainsString(
            'config.loginAttempts = ' . (self::CAPTCHA_THRESHOLD - 1),
            $html,
            'login.blade.php must emit config.loginAttempts (THRESHOLD-1) from the captcha_failed_attempts session key'
        );

        // Part 2: a wrong-password attempt with counter at THRESHOLD-1 must push it to THRESHOLD.
        // Use an unknown username so the server uses session counter+1 (not the DB counter).
        $this->postLogin(
            ['username' => 'nobody@doesnotexist.example', 'password' => 'wrong-password'],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD - 1]
        );

        // After postLogin(), the session driver holds the session the POST wrote to.
        // Use its ID so the GET loads the updated captcha_failed_attempts = CAPTCHA_THRESHOLD.
        $html = $this->call('GET', self::LOGIN_URL, [], $this->makeEncryptedSessionCookie())->getContent();

        $this->assertStringContainsString('captchaPublicKey', $html);
        $this->assertStringContainsString('maxLoginAttempts2ShowCaptcha', $html);
        $this->assertStringContainsString(
            'config.loginAttempts = ' . self::CAPTCHA_THRESHOLD,
            $html,
            'login.blade.php must emit config.loginAttempts (THRESHOLD) from the captcha_failed_attempts session key'
        );
    }

    public function testLoginScreenEmitsLoginAttemptsFromSessionKey(): void
    {
        // GET establishes the session.
        $this->call('GET', self::LOGIN_URL);

        // Inject a known value so we can assert the blade reads exactly this key.
        $expectedAttempts = self::CAPTCHA_THRESHOLD + 1;
        $this->app['session']->driver()->put('captcha_failed_attempts', $expectedAttempts);
        $this->app['session']->driver()->save();

        // Re-send the session cookie so the next GET kernel cycle loads the same session.

        $html = $this->call('GET', self::LOGIN_URL, [], $this->makeEncryptedSessionCookie())->getContent();

        // The blade wraps this in `@if(Session::has('captcha_failed_attempts'))` and emits:
        //   config.loginAttempts = <value>;
        // If the blade reads a different key, this assertion fails.
        $this->assertStringContainsString(
            'config.loginAttempts = ' . $expectedAttempts,
            $html,
            'login.blade.php must emit config.loginAttempts from captcha_failed_attempts, not any other session key'
        );
    }

    // -------------------------------------------------------------------------
    // 5. Auth form submission when Turnstile is expired or not solved
    // -------------------------------------------------------------------------

    public function testExpiredTurnstileTokenFailsValidation(): void
    {
        $this->fakeTurnstileFail();

        $this->postLogin(
            ['cf-turnstile-response' => 'expired-or-invalid-token'],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]
        );

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'An expired or invalid Turnstile token must produce a validation error'
        );
    }

    public function testUnsolvedCaptchaEmptyTokenFailsValidation(): void
    {
        $this->postLogin(
            ['cf-turnstile-response' => ''],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]
        );

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'An empty Turnstile response must be rejected by the required rule'
        );
    }

    // -------------------------------------------------------------------------
    // 6. Request-body login_attempts is ignored; only session counter matters
    // -------------------------------------------------------------------------

    public function testRequestSuppliedLoginAttemptsIsIgnored(): void
    {
        // Session counter is at threshold but the POST body claims login_attempts=0.
        // The captcha gate must still fire because the server ignores the body field.
        $this->postLogin(
            ['login_attempts' => 0],  // attacker-supplied body value: below threshold
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]  // server session: at threshold
        );

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'cf-turnstile-response must be required based on the session counter, not the request body'
        );
    }

    // -------------------------------------------------------------------------
    // 7. Enumeration safety: captcha fires for non-existent users too
    // -------------------------------------------------------------------------

    public function testCaptchaRequiredForUnknownUsernameWhenSessionAtThreshold(): void
    {
        // A non-existent username must still require captcha when the session counter
        // is at threshold — no oracle for whether the account exists.
        $this->postLogin(
            [
                'username' => 'nobody@doesnotexist.example',
                'password' => 'irrelevant',
            ],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]
        );

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'cf-turnstile-response must be required for non-existent users when session counter is at threshold'
        );
    }

    public function testRepeatedUnknownUserFailuresIncrementSessionCounterToThreshold(): void
    {
        // GET establishes the session and CSRF token.
        $this->call('GET', self::LOGIN_URL);

        // Make CAPTCHA_THRESHOLD failed attempts with a non-existent username,
        // replaying the same session cookie so the server accumulates the counter.
        for ($i = 0; $i < self::CAPTCHA_THRESHOLD; $i++) {
            $this->call('POST', self::LOGIN_URL, [
                'username' => 'nobody@doesnotexist.example',
                'password' => 'irrelevant',
                'flow' => 'password',
                '_token' => Session::token(),
            ], $this->makeEncryptedSessionCookie());
        }

        // One more attempt without a captcha token — the session counter must now
        // be at threshold, so cf-turnstile-response is required even for a
        // non-existent user (no request-body shortcut available to the attacker).
        $this->call('POST', self::LOGIN_URL, [
            'username' => 'nobody@doesnotexist.example',
            'password' => 'irrelevant',
            'flow' => 'password',
            '_token' => Session::token(),
        ], $this->makeEncryptedSessionCookie());

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'After ' . self::CAPTCHA_THRESHOLD . ' failed attempts with an unknown username, cf-turnstile-response must be required'
        );
    }

    public function testRepeatedKnownUserFailuresIncrementSessionCounterByOnePerAttempt(): void
    {
        // GET establishes the session and CSRF token.
        $this->call('GET', self::LOGIN_URL);

        // Make CAPTCHA_THRESHOLD failed attempts with a real account and a wrong password,
        // replaying the same session cookie so the server accumulates the counter.
        // LockUserCounterMeasure already increments the DB counter via CustomAuthProvider;
        // the controller must NOT double-increment — each failure must add exactly 1 to the session.
        for ($i = 0; $i < self::CAPTCHA_THRESHOLD; $i++) {
            $this->call('POST', self::LOGIN_URL, [
                'username' => $this->testEmail,
                'password' => 'definitely-wrong-password',
                'flow' => 'password',
                '_token' => Session::token(),
            ], $this->makeEncryptedSessionCookie());
        }

        $sessionCounter = $this->app['session']->driver()->get('captcha_failed_attempts');
        $this->assertEquals(
            self::CAPTCHA_THRESHOLD,
            $sessionCounter,
            'Each failed attempt must increment the session counter by exactly 1 (not 2). ' .
            'A double-increment here would re-introduce an enumeration oracle: known users would ' .
            'hit the captcha threshold faster than unknown users.'
        );
    }

    // -------------------------------------------------------------------------
    // 8. Successful login resets the session counter
    // -------------------------------------------------------------------------

    public function testSuccessfulLoginClearsSessionCounter(): void
    {
        $this->fakeTurnstilePass();

        $this->postLogin(
            ['cf-turnstile-response' => 'valid-token'],
            ['captcha_failed_attempts' => self::CAPTCHA_THRESHOLD]
        );

        $this->assertNull(
            $this->app['session']->driver()->get('captcha_failed_attempts'),
            'captcha_failed_attempts must be removed from session after a successful login'
        );
    }
}
