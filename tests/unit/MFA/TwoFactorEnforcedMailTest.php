<?php namespace Tests\unit\MFA;
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

use App\Mail\TwoFactorEnforcedMail;
use Auth\User;
use Tests\BrowserKitTestCase;

/**
 * Class TwoFactorEnforcedMailTest
 * @package Tests\unit\MFA
 */
final class TwoFactorEnforcedMailTest extends BrowserKitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // the sender comes from env vars that CI does not define
        config(['mail.from' => ['address' => 'noreply@example.com', 'name' => 'noreply']]);
    }

    public function testUserControlledNameIsEscaped(): void
    {
        $user = new User();
        $user->setEmail('mail-escape@nomail.com');
        $user->setFirstName('<img src=x onerror=alert(1)>');
        $user->setLastName('<a href="https://evil.example">click</a>');

        $html = (new TwoFactorEnforcedMail($user))->render();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<a href="https://evil.example">', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testDoesNotClaimThatRecoveryCodesWereAlreadySetUp(): void
    {
        $user = new User();
        $user->setEmail('mail-text@nomail.com');
        $user->setFirstName('Jane');
        $user->setLastName('Doe');

        $html = (new TwoFactorEnforcedMail($user))->render();

        $this->assertStringContainsString('Jane Doe', $html);
        $this->assertStringContainsString('/accounts/user/profile', $html);
        $this->assertStringNotContainsString('have been set up', $html);
    }
}
