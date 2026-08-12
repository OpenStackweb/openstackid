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

use Auth\User;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

/**
 * Class UserLoginTurnstileTest
 *
 * Covers Cloudflare Turnstile integration in UserController::postLogin():
 *  - cf-turnstile-response required when login_attempts (from request body) >= threshold
 *  - threshold gating (before / at boundary / above boundary)
 *  - omitted login_attempts field defaults to zero (no captcha required)
 *  - captcha is gated on the request-body counter
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

    private function getTestUser(): User
    {
        return EntityManager::getRepository(User::class)
            ->findOneBy(['identifier' => 'sebastian.marcet']);
    }

    private function postLogin(array $overrides = [])
    {
        // GET the login page first so the session (and its CSRF token) is established,
        // mirroring how a real browser submits the form.
        $this->call('GET', self::LOGIN_URL);

        return $this->call('POST', self::LOGIN_URL, array_merge([
            'username' => $this->testEmail,
            'password' => $this->testPassword,
            'flow' => 'password',
            '_token' => Session::token(),
        ], $overrides));
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

    // -------------------------------------------------------------------------
    // 1. Validation failure when cf-turnstile-response is missing
    // -------------------------------------------------------------------------

    public function testMissingTurnstileResponseFailsValidationWhenAtThreshold(): void
    {
        $user = $this->getTestUser();

        $this->postLogin([
            "login_attempts" => self::CAPTCHA_THRESHOLD,
        ]); // no cf-turnstile-response

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'Expected a validation error for cf-turnstile-response when user is at threshold'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Login flow behaviour before / after CAPTCHA threshold
    // -------------------------------------------------------------------------

    public function testLoginBelowThresholdDoesNotRequireTurnstile(): void
    {
        $user = $this->getTestUser();

        $this->postLogin([
            'login_attempts' => self::CAPTCHA_THRESHOLD - 1
        ]); // correct credentials, no captcha token

        $this->assertFalse(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'Turnstile must not be required when login attempts are below threshold'
        );
    }

    public function testLoginAtThresholdWithValidTokenPassesValidation(): void
    {
        $user = $this->getTestUser();

        $this->fakeTurnstilePass();

        $this->postLogin([
            'cf-turnstile-response' => 'dummy-token-accepted-by-mock',
            'login_attempts' => self::CAPTCHA_THRESHOLD
        ]);

        $this->assertFalse(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'A valid Turnstile token must clear the captcha validation rule'
        );
    }

    public function testOmittedLoginAttemptsFieldDefaultsToZeroNoCaptchaRequired(): void
    {
        // No login_attempts key posted → intval(null) = 0 → below threshold →
        // captcha rule is never added to the validator.
        $this->postLogin([
            'username' => 'nobody@doesnotexist.example',
            'password' => 'irrelevant',
        ]);

        $this->assertFalse(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'Turnstile must not be required when login_attempts is absent from the request'
        );
    }

    // -------------------------------------------------------------------------
    // 4. Rendering of Turnstile on the thresholded login screen
    // -------------------------------------------------------------------------

    public function testLoginScreenIncludesTurnstileConfigWhenAboveThreshold(): void
    {
        $user = $this->getTestUser();

        // Place user one below threshold; the wrong-password attempt crosses it.
        $this->postLogin([
            'password' => 'wrong-password',
            'login_attempts' => self::CAPTCHA_THRESHOLD - 1
        ]);

        // errorLogin() flashes max_login_attempts_2_show_captcha into the session;
        // following the redirect renders login.blade.php which emits those values.
        $html = $this->call('GET', self::LOGIN_URL)->getContent();

        // captchaPublicKey is always rendered (login.blade.php, not conditional)
        $this->assertStringContainsString('captchaPublicKey', $html);

        // maxLoginAttempts2ShowCaptcha is emitted when the session key is set
        $this->assertStringContainsString('maxLoginAttempts2ShowCaptcha', $html);
    }

    // -------------------------------------------------------------------------
    // 5. Auth form submission when Turnstile is expired or not solved
    // -------------------------------------------------------------------------

    public function testExpiredTurnstileTokenFailsValidation(): void
    {
        $user = $this->getTestUser();

        // Cloudflare API returns success=false (expired / already-used token)
        $this->fakeTurnstileFail();

        $this->postLogin([
            'cf-turnstile-response' => 'expired-or-invalid-token',
            'login_attempts' => self::CAPTCHA_THRESHOLD
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'An expired or invalid Turnstile token must produce a validation error'
        );
    }

    public function testUnsolvedCaptchaEmptyTokenFailsValidation(): void
    {
        $user = $this->getTestUser();

        // Empty string triggers the 'required' rule before any Cloudflare call
        $this->postLogin([
            'cf-turnstile-response' => '',
            'login_attempts' => self::CAPTCHA_THRESHOLD
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'An empty Turnstile response must be rejected by the required rule'
        );
    }
}