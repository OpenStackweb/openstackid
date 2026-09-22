<?php namespace Tests\unit;

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

use App\Mail\UserEmailVerificationRequest;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\BrowserKitTestCase;

class EmailVerificationViewTest extends BrowserKitTestCase
{
    protected function prepareForTests(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '127.0.0.1';
        Queue::fake();
        Mail::fake();
    }

    private function makeUser(string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);
        $user->method('getFullName')->willReturn('Preview User');
        return $user;
    }

    public function testFnVerificationEmailRendersAndSends(): void
    {
        Config::set('app.tenant_name', 'FNTECH');

        $email = 'preview+fntech@example.com';
        $verificationLink = 'https://id.fntech.com/verify/abc123deadbeef';

        $mailable = new UserEmailVerificationRequest($this->makeUser($email), $verificationLink);

        $mailable->assertHasSubject(
            sprintf('[%s] Email verification required', Config::get('app.app_name'))
        );
        $mailable->assertTo($email);

        $html = $mailable->render();

        $this->assertStringContainsString('Confirm your email.', $html);
        $this->assertStringContainsString($email, $html);
        $this->assertStringContainsString($verificationLink, $html);
        $this->assertStringContainsString('Verify email', $html);
        $this->assertStringContainsString('paste this link', $html);
        $this->assertStringContainsString('Questions?', $html);
        $this->assertStringContainsString(Config::get('app.help_email'), $html);
        $this->assertStringNotContainsString('edit your profile', $html);
    }
}
