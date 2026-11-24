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
use App\Http\Middleware\VerifyCsrfToken;
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;

/**
 * Class AdminApiCsrfTest
 *
 * Runs the session-authenticated admin API with the FULL middleware stack.
 * VerifyCsrfToken skips itself when APP_ENV=testing, so the tests bind a subclass that never does;
 * nothing here depends on APP_ENV or on withoutMiddleware().
 * @package Tests
 */
class AdminApiCsrfTest extends BrowserKitTestCase
{
    private const TOKEN = 'csrf-token-for-tests';

    // the admin routes sit behind the `ssl` middleware, which redirects plain http
    protected $baseUrl = 'https://localhost';

    private User $admin;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();

        // enforce CSRF even though the app runs as APP_ENV=testing
        $this->app->bind(VerifyCsrfToken::class, EnforcedVerifyCsrfToken::class);

        $this->admin = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->be($this->admin);
        Session::start();
        $this->withSession(['_token' => self::TOKEN]);
    }

    private function csrfHeaders(): array
    {
        return ['HTTP_X-CSRF-TOKEN' => self::TOKEN];
    }

    private function group(string $slug): Group
    {
        return EntityManager::getRepository(Group::class)->findOneBy(['slug' => $slug]);
    }

    private function targetUser(): User
    {
        return EntityManager::getRepository(User::class)->findOneBy(['identifier' => '6']);
    }

    private function isInGroup(int $userId, string $slug): bool
    {
        EntityManager::clear();
        $user = EntityManager::getRepository(User::class)->find($userId);
        return $user !== null && $user->belongToGroup($slug);
    }

    private function client(): Client
    {
        return EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_test_app']);
    }

    // routes

    public function testEveryMutatingAdminRouteHasCsrf(): void
    {
        $checked = 0;
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'admin/') && $uri !== 'admin') continue;
            $mutating = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);
            if (empty($mutating)) continue;
            $checked++;
            $middleware = array_map(fn($m) => is_string($m) ? $m : 'closure', $route->gatherMiddleware());
            if (!in_array('csrf', $middleware, true) && !in_array(VerifyCsrfToken::class, $middleware, true)) {
                $missing[] = implode('|', $mutating) . ' ' . $uri;
            }
        }
        $this->assertGreaterThan(60, $checked, 'expected the admin routes to be registered');
        $this->assertSame([], $missing, "routes without csrf:\n" . implode("\n", $missing));
    }

    // PUT groups/{id}/users/{user_id}

    public function testAddUserToGroupWithoutTokenIsRejected(): void
    {
        $user = $this->targetUser();
        $group = $this->group(IGroupSlugs::RawUsersGroup);
        $uid = $user->getId();
        $this->assertFalse($this->isInGroup($uid, IGroupSlugs::RawUsersGroup));

        // what a form on another site sends: POST + _method=PUT, with the session cookie but no token
        $this->call('POST', "/admin/api/v1/groups/{$group->getId()}/users/{$uid}", ['_method' => 'PUT']);

        $this->assertResponseStatus(419);
        $this->assertFalse($this->isInGroup($uid, IGroupSlugs::RawUsersGroup));
    }

    public function testAddUserToGroupWithWrongTokenIsRejected(): void
    {
        $user = $this->targetUser();
        $group = $this->group(IGroupSlugs::RawUsersGroup);
        $uid = $user->getId();

        $this->call('PUT', "/admin/api/v1/groups/{$group->getId()}/users/{$uid}", [], [], [], ['HTTP_X-CSRF-TOKEN' => 'wrong']);

        $this->assertResponseStatus(419);
        $this->assertFalse($this->isInGroup($uid, IGroupSlugs::RawUsersGroup));
    }

    public function testAddUserToGroupWithTokenSucceeds(): void
    {
        $user = $this->targetUser();
        $group = $this->group(IGroupSlugs::RawUsersGroup);
        $uid = $user->getId();

        $this->call('PUT', "/admin/api/v1/groups/{$group->getId()}/users/{$uid}", [], [], [], $this->csrfHeaders());

        $this->assertResponseStatus(201);
        $this->assertTrue($this->isInGroup($uid, IGroupSlugs::RawUsersGroup));
    }

    // DELETE users/{id}

    public function testDeleteUserWithoutTokenIsRejected(): void
    {
        $uid = $this->targetUser()->getId();

        $this->call('POST', "/admin/api/v1/users/{$uid}", ['_method' => 'DELETE']);

        $this->assertResponseStatus(419);
        EntityManager::clear();
        $this->assertNotNull(EntityManager::getRepository(User::class)->find($uid));
    }

    public function testDeleteUserWithTokenSucceeds(): void
    {
        $uid = $this->targetUser()->getId();

        $this->call('DELETE', "/admin/api/v1/users/{$uid}", [], [], [], $this->csrfHeaders());

        $this->assertResponseStatus(204);
        EntityManager::clear();
        $this->assertNull(EntityManager::getRepository(User::class)->find($uid));
    }

    // PUT clients/{id}/secret

    public function testRegenerateClientSecretWithoutTokenIsRejected(): void
    {
        $client = $this->client();
        $id = $client->getId();
        $secret = $client->getClientSecret();

        $this->call('POST', "/admin/api/v1/clients/{$id}/secret", ['_method' => 'PUT']);

        $this->assertResponseStatus(419);
        EntityManager::clear();
        $this->assertSame($secret, EntityManager::getRepository(Client::class)->find($id)->getClientSecret());
    }

    public function testRegenerateClientSecretWithTokenSucceeds(): void
    {
        $client = $this->client();
        $id = $client->getId();
        $secret = $client->getClientSecret();

        $this->call('PUT', "/admin/api/v1/clients/{$id}/secret", [], [], [], $this->csrfHeaders());

        $this->assertResponseStatus(201);
        EntityManager::clear();
        $this->assertNotSame($secret, EntityManager::getRepository(Client::class)->find($id)->getClientSecret());
    }

    // reads and the public pages are not affected

    public function testReadsDoNotNeedAToken(): void
    {
        $this->call('GET', '/admin/api/v1/groups');
        $this->assertResponseStatus(200);
    }
}

/**
 * VerifyCsrfToken that does not skip itself under APP_ENV=testing.
 */
class EnforcedVerifyCsrfToken extends VerifyCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
