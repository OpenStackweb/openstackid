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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Mockery;
use OAuth2\Services\IMementoOAuth2SerializerService;
use SocialiteProviders\Apple\Provider as AppleProvider;

/**
 * Class UserLogoutPreservesPendingOAuth2RequestTest
 *
 * A relying party sends the user to /oauth2/auth while the IDP session is already logged in as
 * somebody else. To switch accounts the user goes through the IDP web logout
 * (UserController@logout) and signs in again from the login page. That logout flushes the whole
 * session; the pending OAuth2 authorization request (the memento) has to survive it, otherwise the
 * second login runs under the DEFAULT strategy, lands on the user's identity page and the relying
 * party never receives its callback.
 */
final class UserLogoutPreservesPendingOAuth2RequestTest extends OpenStackIDBaseTestCase
{
    private const Provider = 'apple';
    // seeded by TestSeeder
    private const ClientId = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
    private const RedirectUri = 'https://www.test.com:443/oauth2';
    private const LoggedUserEmail = 'sebastian@tipit.net';
    private const NewUserEmail = 'tipitllc@example.com';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $user = EntityManager::getRepository(User::class)->findOneBy(['email' => self::LoggedUserEmail]);
        Session::start();
        $this->be($user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function oauth2RequestParams(): array
    {
        return [
            'client_id' => self::ClientId,
            'redirect_uri' => self::RedirectUri,
            'response_type' => 'code',
            'scope' => sprintf('%s/resource-server/read', Config::get('app.url')),
        ];
    }

    private function memento(): IMementoOAuth2SerializerService
    {
        return app(IMementoOAuth2SerializerService::class);
    }

    /**
     * The relying party starts an authorization while the IDP session already belongs to a user:
     * the IDP stores the request in session and sends the user to the consent page.
     */
    private function startOAuth2FlowAsLoggedUser(): void
    {
        $this->action('POST', "OAuth2\OAuth2ProviderController@auth", $this->oauth2RequestParams());
        $this->assertResponseStatus(302);
        $this->assertTrue($this->memento()->exists(), 'The authorization request should be pending in session.');
    }

    private function webLogout(): void
    {
        $response = $this->call('GET', '/accounts/user/logout');
        $this->assertResponseStatus(302);
        $this->assertEquals(url()->action('UserController@getLogin'), $response->getTargetUrl());
        $this->assertTrue(Auth::guest(), 'The web logout should end the IDP session.');
    }

    private function startSocialLogin(): string
    {
        $this->call('GET', sprintf('/auth/login/%s', self::Provider));
        $this->assertResponseStatus(302);
        $state = Session::get('state');
        $this->assertNotEmpty($state, 'Socialite should have stored its state in session.');
        return $state;
    }

    private function mockProviderUser(string $email): void
    {
        $social_user = (new SocialiteUser())->map([
            'id' => '001234.abcdef.5678',
            'nickname' => null,
            'name' => 'Tipit Llc',
            'email' => $email,
            'avatar' => null,
        ]);

        $driver = Mockery::mock(AppleProvider::class);
        $driver->shouldReceive('user')->once()->andReturn($social_user);
        Socialite::shouldReceive('driver')->with(self::Provider)->andReturn($driver);
    }

    private function postSocialCallback(string $state)
    {
        return $this->call('POST', sprintf('/auth/login/%s/callback', self::Provider), [
            'state' => $state,
            'code' => 'c0de',
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    /**
     * Regression: the web logout must not drop the relying party's pending request.
     */
    public function testWebLogoutKeepsPendingOAuth2Request()
    {
        $this->startOAuth2FlowAsLoggedUser();

        $this->webLogout();

        $this->assertTrue($this->memento()->exists(), 'The pending OAuth2 request was lost on logout.');
        $state = $this->memento()->load()->getState();
        $this->assertEquals(self::ClientId, $state['client_id']);
        $this->assertEquals(self::RedirectUri, $state['redirect_uri']);
    }

    /**
     * Regression (JP's report, 2026-09-25): logged in as user A, the relying party starts an
     * authorization, the user logs out from the IDP page and signs in with Apple as a brand-new
     * account. The callback has to send them back into the OAuth2 flow, not to their identity page.
     */
    public function testSocialLoginAsNewUserAfterWebLogoutReturnsToRelyingParty()
    {
        $this->startOAuth2FlowAsLoggedUser();
        $this->webLogout();

        $state = $this->startSocialLogin();
        $this->mockProviderUser(self::NewUserEmail);
        $response = $this->postSocialCallback($state);

        $this->assertResponseStatus(302);
        $this->assertEquals(url()->action('OAuth2\OAuth2ProviderController@auth'), $response->getTargetUrl());
        $this->assertTrue(Auth::check());
        $this->assertEquals(self::NewUserEmail, Auth::user()->getEmail());
    }

    /**
     * Baseline: a logout outside any authorization flow leaves no OAuth2 state behind.
     */
    public function testWebLogoutWithoutPendingRequestLeavesNoOAuth2State()
    {
        $this->assertFalse($this->memento()->exists());

        $this->webLogout();

        $this->assertFalse($this->memento()->exists());
    }
}
