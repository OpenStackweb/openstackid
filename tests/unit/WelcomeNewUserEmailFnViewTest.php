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

use App\Mail\WelcomeNewUserEmail;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\BrowserKitTestCase;

/**
 * Class WelcomeNewUserEmailFnViewTest
 *
 * Renders and delivers the FNTECH welcome email view to the configured mailer
 * (Mailtrap) for visual preview, covering both the full case (password link +
 * incomplete profile) and the minimal case (no password link, complete profile).
 *
 * @package Tests\unit
 */
class WelcomeNewUserEmailFnViewTest extends BrowserKitTestCase
{
    protected function prepareForTests(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '127.0.0.1';
        Queue::fake();
        Mail::fake();
        Config::set('app.tenant_name', 'FNTECH');
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
        $user->method('createdByOTP')->willReturn(false);
        return $user;
    }

    public function testWelcomeEmailWithPasswordLinkAndIncompleteProfile(): void
    {
        $email = 'preview+welcome@example.com';
        $resetLink = 'https://id.fntech.com/reset/abc123deadbeef';

        $mailable = new WelcomeNewUserEmail($this->makeUser($email, false), $resetLink);

        $mailable->assertHasSubject(
            sprintf('[%1$s] You now have an %1$s', Config::get('app.app_name'))
        );
        $mailable->assertTo($email);

        $html = $mailable->render();

        $this->assertStringContainsString('Your FNid is ready.', $html);
        $this->assertStringContainsString($email, $html);
        // Step 1 — password
        $this->assertStringContainsString('Set your password', $html);
        $this->assertStringContainsString($resetLink, $html);
        // Step 2 — profile
        $this->assertStringContainsString('Complete your profile', $html);
        $this->assertStringContainsString('Open profile', $html);
        // Disclaimer and footer
        $this->assertStringContainsString('You stay in control', $html);
        $this->assertStringContainsString(Config::get('app.help_email'), $html);

    }

    public function testWelcomeEmailWithoutPasswordLinkAndCompleteProfile(): void
    {
        $email = 'preview+welcome-complete@example.com';

        $mailable = new WelcomeNewUserEmail($this->makeUser($email, true), null);

        $mailable->assertHasSubject(
            sprintf('[%1$s] You now have an %1$s', Config::get('app.app_name'))
        );
        $mailable->assertTo($email);

        $html = $mailable->render();

        $this->assertStringContainsString('Your FNid is ready.', $html);
        $this->assertStringContainsString($email, $html);
        // Neither step should appear
        $this->assertStringNotContainsString('Set your password', $html);
        $this->assertStringNotContainsString('Complete your profile', $html);
        // Disclaimer and footer always present
        $this->assertStringContainsString('You stay in control', $html);
        $this->assertStringContainsString(Config::get('app.help_email'), $html);

    }
}
