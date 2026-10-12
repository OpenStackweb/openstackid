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

use Auth\Repositories\IUserRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OAuth2\OAuth2Protocol;
use OAuth2\Services\IMementoOAuth2SerializerService;
use Utils\Services\IAuthService;
use Utils\Services\UtilsServiceCatalog;

/**
 * Class ConsentSwitchAccountTest
 *
 * "Not you? Switch account" on the OAuth2 consent screen: the IDP session of the current user is ended
 * but the pending authorization request (the memento) must survive so the flow resumes for whoever logs in next.
 */
final class ConsentSwitchAccountTest extends OpenStackIDBaseTestCase
{
    // seeded by TestSeeder
    private const ClientId = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
    private const RedirectUri = 'https://www.test.com/oauth2';
    private const UserA = 'sebastian@tipit.net';
    private const UserB = 'mkiss@tipit.net';
    private const Password = '1Qaz2wsx!';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        App::singleton(UtilsServiceCatalog::ServerConfigurationService, StubServerConfigurationService::class);
        Session::start();
    }

    private function authorizeParams(array $extra = []): array
    {
        return array_merge([
            'client_id' => self::ClientId,
            'redirect_uri' => self::RedirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => 's123',
        ], $extra);
    }

    private function mementoState(): ?array
    {
        $memento = app(IMementoOAuth2SerializerService::class)->load();
        return is_null($memento) ? null : $memento->getState();
    }

    /**
     * Starts an authorize request as a guest, logs in with the given user and resumes it,
     * leaving the user on the consent screen.
     */
    private function loginAndReachConsent(string $username, array $extra = []): void
    {
        $this->action('POST', 'OAuth2\OAuth2ProviderController@auth', $this->authorizeParams($extra));
        $this->assertResponseStatus(302);

        $this->loginAndResume($username);
    }

    private function loginAndResume(string $username): void
    {
        $response = $this->action('POST', 'UserController@postLogin', [
            'username' => $username,
            'password' => self::Password,
            'flow' => 'password',
            '_token' => Session::token(),
        ]);
        $this->assertResponseStatus(302);
        $this->assertTrue(Auth::check(), sprintf('login failed for %s: %s', $username, $response->getTargetUrl()));

        $response = $this->action('GET', 'OAuth2\OAuth2ProviderController@auth');
        $this->assertResponseStatus(302);
        $this->assertStringContainsString('/accounts/user/consent', $response->getTargetUrl());
    }

    private function postSwitchAccount()
    {
        return $this->action('POST', 'UserController@switchAccount', ['_token' => Session::token()]);
    }

    public function testSwitchAccountKeepsAuthorizationRequestAndResumesForNewUser(): void
    {
        $this->loginAndReachConsent(self::UserA);

        $response = $this->action('GET', 'UserController@getConsent');
        $this->assertResponseStatus(200);
        $this->assertStringContainsString('Not you?', $response->getContent());
        $this->assertStringContainsString('Switch account', $response->getContent());

        $response = $this->postSwitchAccount();

        $this->assertResponseStatus(302);
        $this->assertStringContainsString('/auth/login', $response->getTargetUrl());
        $this->assertTrue(Auth::guest());

        $state = $this->mementoState();
        $this->assertNotNull($state, 'the pending authorization request must survive the switch');
        $this->assertSame(self::ClientId, $state['client_id']);
        $this->assertSame(self::RedirectUri, $state['redirect_uri']);
        $this->assertSame('s123', $state['state']);

        // the new user logs in and the same request resumes for them
        $this->loginAndResume(self::UserB);
        $this->assertSame(self::UserB, Auth::user()->getEmail());

        $this->action('POST', 'UserController@postConsent', [
            'trust' => IAuthService::AuthorizationResponse_AllowOnce,
            '_token' => Session::token(),
        ]);
        $this->assertResponseStatus(302);

        $response = $this->action('GET', 'OAuth2\OAuth2ProviderController@auth');
        $this->assertResponseStatus(302);

        parse_str(parse_url($response->getTargetUrl(), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query);
        $this->assertSame('s123', $query['state']);
        $this->assertStringStartsWith(self::RedirectUri, $response->getTargetUrl());
    }

    public function testSwitchAccountReopensConsentPromptButKeepsLoginPromptProcessed(): void
    {
        $this->loginAndReachConsent(self::UserA, [
            OAuth2Protocol::OAuth2Protocol_Prompt => 'login consent',
        ]);

        $before = explode(' ', $this->mementoState()['processed_params'] ?? '');
        $this->assertContains(OAuth2Protocol::OAuth2Protocol_Prompt_Consent, $before);

        $this->postSwitchAccount();
        $this->assertResponseStatus(302);

        $after = explode(' ', $this->mementoState()['processed_params'] ?? '');
        $this->assertNotContains(OAuth2Protocol::OAuth2Protocol_Prompt_Consent, $after);
        $this->assertContains(OAuth2Protocol::OAuth2Protocol_Prompt_Login, $after);
    }

    public function testConsentHidesSwitchAccountWhenRequestIsPinnedToAUser(): void
    {
        $this->loginAndReachConsent(self::UserA, [
            OAuth2Protocol::OAuth2Protocol_LoginHint => self::UserA,
        ]);

        $response = $this->action('GET', 'UserController@getConsent');
        $this->assertResponseStatus(200);
        $this->assertStringNotContainsString('Switch account', $response->getContent());
    }

    public function testSwitchAccountIsRefusedWhenRequestIsPinnedToAUser(): void
    {
        $this->loginAndReachConsent(self::UserA, [
            OAuth2Protocol::OAuth2Protocol_LoginHint => self::UserA,
        ]);
        $state = $this->mementoState();

        $this->postSwitchAccount();

        $this->assertResponseStatus(400);
        $this->assertTrue(Auth::check(), 'a pinned request must not log the user out');
        $this->assertSame($state, $this->mementoState());
    }

    public function testSwitchAccountWithoutPendingOAuth2RequestIsRejected(): void
    {
        Auth::login(app(IUserRepository::class)->getByEmailOrName(self::UserA));

        $this->postSwitchAccount();

        $this->assertResponseStatus(400);
        $this->assertTrue(Auth::check());
    }

    public function testSwitchAccountDoesNotRespondToGet(): void
    {
        $this->loginAndReachConsent(self::UserA);

        $this->call('GET', '/accounts/user/consent/switch-account');

        $this->assertResponseStatus(405);
        $this->assertTrue(Auth::check());
    }
}
