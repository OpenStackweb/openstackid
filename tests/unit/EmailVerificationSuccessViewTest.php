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

use App\Mail\UserEmailVerificationSuccess;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\BrowserKitTestCase;

class EmailVerificationSuccessViewTest extends BrowserKitTestCase
{
    protected function prepareForTests(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '127.0.0.1';
        Queue::fake();
        Mail::fake();
    }

    private function makeUser(string $email, bool $complete): User
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);
        $user->method('getFullName')->willReturn('Preview User');
        $user->method('getFirstName')->willReturn($complete ? 'Preview' : null);
        $user->method('getLastName')->willReturn($complete ? 'User' : null);
        $user->method('getCompany')->willReturn($complete ? 'ACME Inc.' : null);
        $user->method('getCountry')->willReturn($complete ? 'US' : null);
        return $user;
    }

    public function testVerificationSuccessWithPasswordLinkAndIncompleteProfile(): void
    {
        $email = 'preview+verified@example.com';
        $resetLink = 'https://id.fntech.com/reset/abc123deadbeef';

        $mailable = new UserEmailVerificationSuccess($this->makeUser($email, false), $resetLink);

        $mailable->assertHasSubject(
            sprintf('[%1$s] %1$s Verified', Config::get('app.app_name'))
        );
        $mailable->assertTo($email);

        $html = $mailable->render();

        $this->assertStringContainsString("You're verified.", $html);
        $this->assertStringContainsString($email, $html);
        $this->assertStringContainsString(Config::get('app.app_name'), $html);
        // Both steps present
        $this->assertStringContainsString('Two quick things to finish setup', $html);
        $this->assertStringContainsString('Set your password', $html);
        $this->assertStringContainsString($resetLink, $html);
        $this->assertStringContainsString('Complete your profile', $html);
        $this->assertStringContainsString('Open profile', $html);
        // Footer
        $this->assertStringContainsString(Config::get('app.help_email'), $html);

    }

    public function testVerificationSuccessWithoutPasswordLinkAndCompleteProfile(): void
    {
        $email = 'preview+verified-complete@example.com';

        $mailable = new UserEmailVerificationSuccess($this->makeUser($email, true), null);

        $mailable->assertHasSubject(
            sprintf('[%1$s] %1$s Verified', Config::get('app.app_name'))
        );
        $mailable->assertTo($email);

        $html = $mailable->render();

        $this->assertStringContainsString("You're verified.", $html);
        $this->assertStringContainsString($email, $html);
        // No steps — intro paragraph should be absent
        $this->assertStringNotContainsString('Two quick things to finish setup', $html);
        $this->assertStringNotContainsString('Set your password', $html);
        $this->assertStringNotContainsString('Complete your profile', $html);
        // Footer always present
        $this->assertStringContainsString(Config::get('app.help_email'), $html);

    }
}
