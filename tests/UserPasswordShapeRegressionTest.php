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
use App\Jobs\AddUserAction;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use models\exceptions\ValidationException;

class UserPasswordShapeRegressionTest extends TestCase
{
    private function setPasswordPolicyForTest(): void
    {
        // Keep these aligned with your real config defaults (or just read them from config)
        Config::set('auth.password_min_length', 8);
        Config::set('auth.password_max_length', 128);
        Config::set('auth.password_shape_warning', 'Password does not meet complexity requirements.');

        // IMPORTANT: hyphen is escaped (or move it to end of the class)
        Config::set(
            'auth.password_shape_pattern',
            '^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*+\-])[A-Za-z0-9#?!@$%^&*+\-]+$'
        );
    }

    public static function validPasswordsProvider(): array
    {
        return [
            // has uppercase, lowercase, digit, and "-" special char
            ['Abcdef1-'],
            ['Zz9-aaaaa'],
            ['Aaaaaa1-+'], // multiple specials still ok (if + is allowed)
        ];
    }

    /**
     * @dataProvider validPasswordsProvider
     */
    public function test_set_password_allows_hyphen(string $plainPassword): void
    {
        $this->setPasswordPolicyForTest();

        // Prevent actually queueing anything (setPassword dispatches AddUserAction)
        Queue::fake();

        $user = new User();
        $user->setEmail('test@example.org');

        $user->setPassword($plainPassword);

        $this->assertTrue($user->hasPasswordSet());
        $this->assertNotEmpty($user->getPassword());

    }

    public function test_password_missing_special_character_is_rejected(): void
    {
        $this->setPasswordPolicyForTest();
        Queue::fake();

        $user = new User();
        $user->setEmail('test@example.org');

        $this->expectException(ValidationException::class);
        $user->setPassword('Abcdef12'); // no special char
    }

    public function test_password_with_hyphen_matches_current_regex(): void
    {
        $this->setPasswordPolicyForTest();

        $pattern = Config::get('auth.password_shape_pattern');
        $this->assertSame(1, preg_match("/$pattern/", 'Abcdef1-'));
    }
}
