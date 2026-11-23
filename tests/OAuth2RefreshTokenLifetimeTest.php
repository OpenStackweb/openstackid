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
use Illuminate\Support\Facades\DB;
use Laminas\Crypt\Hash;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;
use OAuth2\Services\ITokenService;
use Utils\Services\UtilsServiceCatalog;

/**
 * Class OAuth2RefreshTokenLifetimeTest
 * Per-client refresh token lifetime override vs the global OAuth2.RefreshToken.Lifetime.
 * @package Tests
 */
final class OAuth2RefreshTokenLifetimeTest extends OpenStackIDBaseTestCase
{
    private const GLOBAL_LIFETIME = 7200;

    private User $user;

    private Client $client_a;

    private Client $client_b;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app->singleton(UtilsServiceCatalog::ServerConfigurationService, StubServerConfigurationService::class);
        return $app;
    }

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $_ENV['refresh.token.lifetime'] = self::GLOBAL_LIFETIME;
        $this->user = EntityManager::getRepository(User::class)->findOneBy(['email' => 'sebastian@tipit.net']);
        $repo = EntityManager::getRepository(Client::class);
        $this->client_a = $repo->findOneBy(['client_id' => '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client']);
        $this->client_b = $repo->findOneBy(['client_id' => '1234/Vcvr6fvQbH4HyNgwKlfSpkce.openstack.client']);
    }

    protected function tearDown(): void
    {
        unset($_ENV['refresh.token.lifetime']);
        parent::tearDown();
    }

    /**
     * Issues a refresh token for the given client and returns [token lifetime, persisted row lifetime]
     */
    private function issueRefreshToken(Client $client): array
    {
        $token_service = app()->make(ITokenService::class);
        $access_token = $token_service->createAccessTokenFromParams(
            $client->getClientId(),
            'openid',
            Config::get('app.url'),
            $this->user->getId()
        );
        $refresh_token = $token_service->createRefreshToken($access_token);
        $row = DB::table('oauth2_refresh_token')
            ->where('value', Hash::compute('sha256', $refresh_token->getValue()))
            ->first();
        $this->assertNotNull($row);
        return [$refresh_token->getLifetime(), intval($row->lifetime)];
    }

    private function setClientLifetime(Client $client, ?int $lifetime): void
    {
        $client->setRefreshTokenLifetime($lifetime);
        EntityManager::persist($client);
        EntityManager::flush();
    }

    public function testClientWithoutOverrideUsesGlobalLifetime(): void
    {
        $this->setClientLifetime($this->client_a, null);
        [$lifetime, $persisted] = $this->issueRefreshToken($this->client_a);
        $this->assertSame(self::GLOBAL_LIFETIME, $lifetime);
        $this->assertSame(self::GLOBAL_LIFETIME, $persisted);
    }

    public function testClientOverrideTakesPrecedenceOverGlobalAndOnlyForThatClient(): void
    {
        $this->setClientLifetime($this->client_a, 600);
        $this->setClientLifetime($this->client_b, null);

        [$lifetime_a, $persisted_a] = $this->issueRefreshToken($this->client_a);
        [$lifetime_b, $persisted_b] = $this->issueRefreshToken($this->client_b);

        $this->assertSame(600, $lifetime_a);
        $this->assertSame(600, $persisted_a);
        $this->assertSame(self::GLOBAL_LIFETIME, $lifetime_b);
        $this->assertSame(self::GLOBAL_LIFETIME, $persisted_b);
    }

    public function testClientOverrideZeroMeansNeverExpiresEvenWithFiniteGlobal(): void
    {
        $this->setClientLifetime($this->client_a, 0);
        [$lifetime, $persisted] = $this->issueRefreshToken($this->client_a);
        $this->assertSame(0, $lifetime);
        $this->assertSame(0, $persisted);
    }

    public function testGlobalZeroIsInheritedWhenNoOverride(): void
    {
        $_ENV['refresh.token.lifetime'] = 0;
        $this->setClientLifetime($this->client_a, null);
        [$lifetime] = $this->issueRefreshToken($this->client_a);
        $this->assertSame(0, $lifetime);
    }
}
