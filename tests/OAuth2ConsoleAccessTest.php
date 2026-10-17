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
use Auth\User;
use Illuminate\Support\Facades\Config;
use Mockery;

/**
 * Class OAuth2ConsoleAccessTest
 * @package Tests
 */
class OAuth2ConsoleAccessTest extends TestCase
{
    private function userInGroups(array $slugs): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('belongToGroup')->andReturnUsing(fn(string $slug) => in_array($slug, $slugs, true));
        return $user;
    }

    public function testDeniedWhenConfigIsEmpty()
    {
        Config::set('oauth2.console_allowed_groups', []);
        // no bypass, not even for super admins
        $this->assertFalse($this->userInGroups(['super-admins', 'oauth2-server-admins'])->canAccessOAuth2Console());
    }

    public function testDeniedWhenConfigIsMissing()
    {
        Config::offsetUnset('oauth2.console_allowed_groups');
        $this->assertFalse($this->userInGroups(['super-admins'])->canAccessOAuth2Console());
    }

    public function testAllowedForMemberOfConfiguredGroup()
    {
        Config::set('oauth2.console_allowed_groups', ['oauth2-console-users', 'sponsors']);
        $this->assertTrue($this->userInGroups(['sponsors'])->canAccessOAuth2Console());
    }

    public function testDeniedForNonMember()
    {
        Config::set('oauth2.console_allowed_groups', ['oauth2-console-users']);
        $this->assertFalse($this->userInGroups(['raw-users', 'super-admins'])->canAccessOAuth2Console());
    }

    public function testEnvParsingIgnoresBlanksAndWhitespace()
    {
        $parse = fn(string $v) => array_values(array_filter(array_map('trim', explode(',', $v))));
        $this->assertSame([], $parse(''));
        $this->assertSame([], $parse(' , ,'));
        $this->assertSame(['a', 'b'], $parse(' a , ,b '));
    }
}
