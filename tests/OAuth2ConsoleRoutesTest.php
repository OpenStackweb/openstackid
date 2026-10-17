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
use App\libs\Auth\Models\IGroupSlugs;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;

/**
 * Class OAuth2ConsoleRoutesTest
 * Real middleware stack (no withoutMiddleware) to verify the OAUTH2 Console group gate.
 * @package Tests
 */
final class OAuth2ConsoleRoutesTest extends OpenStackIDBaseTestCase
{
    private const AllowedSlug = IGroupSlugs::SponsorUsersGroup;

    private bool $plainUserPrepared = false;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        // avoid the http -> https redirect of the 'ssl' middleware masking the gate responses
        Config::set('server.ssl_enabled', false);
    }

    private function findUser(string $identifier): User
    {
        // drop seeder-built in-memory entities so Doctrine hydrates fully initialized ones from the DB
        EntityManager::clear();
        return EntityManager::getRepository(User::class)->findOneBy(['identifier' => $identifier]);
    }

    private function superAdmin(): User
    {
        return $this->findUser('sebastian.marcet');
    }

    private function plainUser(): User
    {
        $user = $this->findUser('2');
        if (!$this->plainUserPrepared) {
            // seeded users are all super admins: strip their groups once to get a plain user
            $user->getGroups()->clear();
            EntityManager::persist($user);
            EntityManager::flush();
            $this->plainUserPrepared = true;
            $this->assertFalse($user->isSuperAdmin());
            $this->assertFalse($user->belongToGroup(self::AllowedSlug));
        }
        return $user;
    }

    private function addToAllowedGroup(User $user): void
    {
        $group = EntityManager::getRepository(Group::class)->findOneBy(['slug' => self::AllowedSlug]);
        $user->addToGroup($group);
        EntityManager::persist($user);
        EntityManager::flush();
    }

    private function webUrls(): array
    {
        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_test_app']);
        return ['/admin/clients', '/admin/grants', '/admin/clients/edit/' . $client->id];
    }

    private function apiUrls(): array
    {
        return [
            '/admin/api/v1/clients',
            '/admin/api/v1/clients/me/access-tokens',
            '/admin/api/v1/clients/me/refresh-tokens',
        ];
    }

    public function testGuestIsRedirectedToLogin()
    {
        Config::set('oauth2.console_allowed_groups', [self::AllowedSlug]);
        $this->call('GET', '/admin/clients');
        $this->assertResponseStatus(302);
        $this->assertStringContainsString('/auth/login', $this->response->headers->get('Location'));
    }

    public function testEverybodyDeniedWhenConfigIsEmpty()
    {
        Config::set('oauth2.console_allowed_groups', []);
        $this->addToAllowedGroup($this->plainUser());

        foreach ([$this->superAdmin(), $this->plainUser()] as $user) {
            $this->be($user);
            foreach ($this->webUrls() as $url) {
                $this->call('GET', $url);
                $this->assertResponseStatus(404, "web $url");
            }
            foreach ($this->apiUrls() as $url) {
                $this->call('GET', $url);
                $this->assertResponseStatus(403, "api $url");
            }
        }
    }

    public function testApiCreateDeniedWhenConfigIsEmpty()
    {
        Config::set('oauth2.console_allowed_groups', []);
        $this->be($this->superAdmin());
        Session::start();
        $before = count(EntityManager::getRepository(Client::class)->findAll());

        $this->call('POST', '/admin/api/v1/clients', ['_token' => Session::token(), 'app_name' => 'blocked_app']);
        $this->assertResponseStatus(403);

        EntityManager::clear();
        $this->assertCount($before, EntityManager::getRepository(Client::class)->findAll());
    }

    public function testAllowedForMemberOfConfiguredGroup()
    {
        Config::set('oauth2.console_allowed_groups', [self::AllowedSlug]);
        $user = $this->plainUser();
        $this->addToAllowedGroup($user);
        $this->be($user);

        foreach ($this->apiUrls() as $url) {
            $this->call('GET', $url);
            $this->assertNotEquals(403, $this->response->getStatusCode(), "api $url");
            $this->assertNotEquals(404, $this->response->getStatusCode(), "api $url");
        }
        foreach (['/admin/clients', '/admin/grants'] as $url) {
            $this->call('GET', $url);
            $this->assertResponseStatus(200, "web $url");
        }
    }

    public function testNonMemberDeniedWhenGroupConfigured()
    {
        Config::set('oauth2.console_allowed_groups', [self::AllowedSlug]);
        // super admin is not in the configured group: no bypass
        foreach ([$this->superAdmin(), $this->plainUser()] as $user) {
            $this->be($user);
            foreach ($this->webUrls() as $url) {
                $this->call('GET', $url);
                $this->assertResponseStatus(404, "web $url");
            }
            foreach ($this->apiUrls() as $url) {
                $this->call('GET', $url);
                $this->assertResponseStatus(403, "api $url");
            }
        }
    }

    public function testMenuVisibilityFollowsGate()
    {
        $user = $this->plainUser();
        // add to the group before authenticating: only super admins can alter memberships
        $this->addToAllowedGroup($user);
        $this->be($user);

        // member of the group but empty config: still denied
        Config::set('oauth2.console_allowed_groups', []);
        $this->call('GET', '/accounts/user/profile');
        $this->assertResponseStatus(200);
        $this->assertStringContainsString('canAccessOAuth2Console: parseInt(\'0\')', $this->response->getContent());

        Config::set('oauth2.console_allowed_groups', [self::AllowedSlug]);
        $this->call('GET', '/accounts/user/profile');
        $this->assertResponseStatus(200);
        $this->assertStringContainsString('canAccessOAuth2Console: parseInt(\'1\')', $this->response->getContent());
    }
}
