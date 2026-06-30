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

use Illuminate\Support\Facades\Session;

/**
 * Class TurnstileProtectedControllersTest
 *
 * Smoke tests verifying that cf-turnstile-response is required on the five auth
 * endpoints that gate every submission behind Turnstile.
 *
 * Requests MUST go over HTTPS (callSecure) because .env.testing sets
 * SSL_ENABLED=true, which causes SSLMiddleware to redirect plain HTTP requests
 * to HTTPS before reaching any controller.
 */
final class TurnstileProtectedControllersTest extends BrowserKitTestCase
{
    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        Session::start();
    }

    private function sessionHasValidationError(string $field): bool
    {
        $errors = $this->app['session']->driver()->get('errors');
        return $errors !== null && $errors->has($field);
    }

    private function postWithSession(string $url, array $data = []): void
    {
        $this->callSecure('GET', $url);
        $this->callSecure('POST', $url, array_merge(['_token' => Session::token()], $data));
    }

    // -------------------------------------------------------------------------
    // RegisterController
    // -------------------------------------------------------------------------

    public function testRegisterRequiresTurnstileToken(): void
    {
        $this->postWithSession('/auth/register', [
            'first_name'      => 'Test',
            'last_name'       => 'User',
            'email'           => 'turnstile-test@example.com',
            'country_iso_code'=> 'US',
            'password'        => 'Abcd1234!',
            'password_confirmation' => 'Abcd1234!',
            // cf-turnstile-response intentionally omitted
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'RegisterController must require cf-turnstile-response'
        );
    }

    // -------------------------------------------------------------------------
    // ForgotPasswordController
    // -------------------------------------------------------------------------

    public function testForgotPasswordRequiresTurnstileToken(): void
    {
        $this->postWithSession('/auth/password/email', [
            'email' => 'anyone@example.com',
            // cf-turnstile-response intentionally omitted
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'ForgotPasswordController must require cf-turnstile-response'
        );
    }

    // -------------------------------------------------------------------------
    // ResetPasswordController
    // -------------------------------------------------------------------------

    public function testResetPasswordRequiresTurnstileToken(): void
    {
        $this->postWithSession('/auth/password/reset', [
            'token'                 => 'any-reset-token',
            'password'              => 'Abcd1234!',
            'password_confirmation' => 'Abcd1234!',
            // cf-turnstile-response intentionally omitted
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'ResetPasswordController must require cf-turnstile-response'
        );
    }

    // -------------------------------------------------------------------------
    // PasswordSetController
    // -------------------------------------------------------------------------

    public function testPasswordSetRequiresTurnstileToken(): void
    {
        $this->postWithSession('/auth/password/set', [
            'token'                 => 'any-set-token',
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'country_iso_code'      => 'US',
            'password'              => 'Abcd1234!',
            'password_confirmation' => 'Abcd1234!',
            // cf-turnstile-response intentionally omitted
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'PasswordSetController must require cf-turnstile-response'
        );
    }

    // -------------------------------------------------------------------------
    // EmailVerificationController
    // -------------------------------------------------------------------------

    public function testEmailVerificationResendRequiresTurnstileToken(): void
    {
        $this->postWithSession('/auth/verification', [
            'email' => 'anyone@example.com',
            // cf-turnstile-response intentionally omitted
        ]);

        $this->assertTrue(
            $this->sessionHasValidationError('cf-turnstile-response'),
            'EmailVerificationController must require cf-turnstile-response'
        );
    }
}
