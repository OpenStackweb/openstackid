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
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\LogRecord;

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

        // Capture on the logger UserService writes to (the default channel, via the Log facade),
        // at debug level, whatever level the channel's own handlers are configured with. The
        // test handler keeps records in memory, so the secrets are never written to disk.
        $capture = new TestHandler(Level::Debug);
        $logger  = Log::getLogger();
        $logger->pushHandler($capture);

        try {
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
        } finally {
            $logger->popHandler();
        }

        $this->assertSame($email, $user->getEmail());

        $log = implode("\n", array_map(
            fn (LogRecord $record) => $record->message . ' ' . json_encode($record->context),
            $capture->getRecords()
        ));
        $this->assertStringContainsString(
            'UserService::registerUser',
            $log,
            'registerUser log lines must be captured for this test to be meaningful'
        );
        $this->assertStringNotContainsString($password, $log, 'password leaked to the log');
        $this->assertStringNotContainsString($captcha, $log, 'Turnstile token leaked to the log');
        $this->assertStringNotContainsString($csrf, $log, 'CSRF token leaked to the log');

        $record = $this->registerUserRecord($capture);
        $this->assertStringContainsString(json_encode($email), $record->message);
        $this->assertStringContainsString('"email"', $record->message);
        $this->assertStringContainsString('"password"', $record->message);
    }

    public function testRegisterUserDoesNotLetFieldNamesForgeLogLines(): void
    {
        // PHP keeps a %0A in a form field name as a real newline, and Laravel's line formatter
        // allows inline line breaks, so an interpolated field name could forge a log line.
        $forged = "extra\n[2026-01-01 00:00:00] production.ERROR: forged";

        $capture = new TestHandler(Level::Debug);
        $logger  = Log::getLogger();
        $logger->pushHandler($capture);

        try {
            $this->app->make(IUserService::class)->registerUser([
                'first_name'            => 'Log',
                'last_name'             => 'Forge',
                'email'                 => '  register-forge@example.com ',
                'country_iso_code'      => 'US',
                'password'              => 'Zx9!plainTextSecret',
                'password_confirmation' => 'Zx9!plainTextSecret',
                $forged                 => '1',
            ]);
        } finally {
            $logger->popHandler();
        }

        $record = $this->registerUserRecord($capture);
        $this->assertStringNotContainsString("\n", $record->message);
        $this->assertStringContainsString('"register-forge@example.com"', $record->message);
        $this->assertStringContainsString(json_encode($forged), $record->message);
        // Written with the formatter Laravel gives its file channels, the record stays one line.
        $line = (new LineFormatter(null, null, true, true, true))->format($record);
        $this->assertSame(1, substr_count($line, "\n"), "registerUser log record spans several lines: {$line}");
    }

    private function registerUserRecord(TestHandler $capture): LogRecord
    {
        foreach ($capture->getRecords() as $record) {
            if (str_starts_with($record->message, 'UserService::registerUser')) {
                return $record;
            }
        }
        $this->fail('no UserService::registerUser log record was captured');
    }
}
