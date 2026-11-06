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

use App\Services\Auth\IUserService;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

/**
 * Class UserServiceRegisterLoggingTest
 *
 * Registration receives the raw sign-up form (password, confirmation, Turnstile token,
 * CSRF token). None of those secrets may reach the application log, at any level.
 */
final class UserServiceRegisterLoggingTest extends BrowserKitTestCase
{
    public function testRegisterUserDoesNotLogCredentialsOrTokens(): void
    {
        $email    = 'register-logging@example.com';
        $password = 'Zx9!plainTextSecret';
        $captcha  = 'turnstile-token-must-not-be-logged';
        $csrf     = 'csrf-token-must-not-be-logged';

        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
            $logged[] = $event->message . ' ' . json_encode($event->context);
        });

        $user = $this->app->make(IUserService::class)->registerUser([
            'first_name'            => 'Log',
            'last_name'             => 'Safe',
            'email'                 => $email,
            'country_iso_code'      => 'US',
            'password'              => $password,
            'password_confirmation' => $password,
            'cf-turnstile-response' => $captcha,
            '_token'                => $csrf,
        ]);

        $this->assertSame($email, $user->getEmail());

        $log = implode("\n", $logged);
        $this->assertStringContainsString(
            'UserService::registerUser',
            $log,
            'registerUser log lines must be captured for this test to be meaningful'
        );
        $this->assertStringNotContainsString($password, $log, 'password leaked to the log');
        $this->assertStringNotContainsString($captcha, $log, 'Turnstile token leaked to the log');
        $this->assertStringNotContainsString($csrf, $log, 'CSRF token leaked to the log');
    }
}
