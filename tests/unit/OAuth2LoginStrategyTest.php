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

use App\libs\OAuth2\Strategies\LoginHintProcessStrategy;
use Illuminate\Container\Container;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Mockery;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\ISecurityContextService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Services\IUserActionService;
use Strategies\OAuth2LoginStrategy;
use Utils\Services\IAuthService;

/**
 * Class OAuth2LoginStrategyTest
 *
 * Tests for OAuth2LoginStrategy focusing on:
 * - Fix B: authenticated users during OAuth2 flow are redirected to the auth
 *   endpoint instead of the user profile page
 * - Regression: the old behavior that sent authenticated users to profile
 *
 * @package Tests\unit
 */
class OAuth2LoginStrategyTest extends TestCase
{
    private $auth_service;
    private $memento_service;
    private $user_action_service;
    private $login_hint_process_strategy;
    private $strategy;
    private $app;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Set up a minimal facade root
        $this->app = new Container();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('debug', 'info', 'warning', 'error', 'critical', 'log')
            ->zeroOrMoreTimes()
            ->andReturnNull();
        $this->app->instance('log', $logger);

        Facade::setFacadeApplication($this->app);

        // Create service mocks
        $this->auth_service = Mockery::mock(IAuthService::class);
        $this->memento_service = Mockery::mock(IMementoOAuth2SerializerService::class);
        $this->user_action_service = Mockery::mock(IUserActionService::class);

        // LoginHintProcessStrategy is final, so we create a real instance with mocked deps
        $security_context_service = Mockery::mock(ISecurityContextService::class);
        $security_context_service->shouldReceive('get')->andReturnNull()->byDefault();
        $this->login_hint_process_strategy = new LoginHintProcessStrategy(
            $this->auth_service,
            $security_context_service
        );

        $this->strategy = new OAuth2LoginStrategy(
            $this->auth_service,
            $this->memento_service,
            $this->user_action_service,
            $this->login_hint_process_strategy
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        parent::tearDown();
    }

    /**
     * Set up the Auth facade mock.
     */
    private function mockAuth(bool $isGuest): void
    {
        $authGuard = Mockery::mock();
        $authGuard->shouldReceive('guest')->andReturn($isGuest);

        $authManager = Mockery::mock();
        $authManager->shouldReceive('guard')->withAnyArgs()->andReturn($authGuard);
        // Auth::guest() forwards to guard()->guest() via __call
        $authManager->shouldReceive('guest')->andReturn($isGuest);

        $this->app->instance('auth', $authManager);
    }

    /**
     * Set up the Redirect facade mock and return it for assertions.
     */
    private function mockRedirect(): Mockery\MockInterface
    {
        $redirector = Mockery::mock();
        $this->app->instance('redirect', $redirector);
        return $redirector;
    }

    // -----------------------------------------------------------------------
    // Fix B: authenticated user redirects to auth endpoint, not profile
    // -----------------------------------------------------------------------

    /**
     * When the user is already authenticated during an OAuth2 flow,
     * getLogin() MUST redirect to the OAuth2 authorization endpoint
     * so the flow can continue.
     */
    public function testGetLoginRedirectsToAuthEndpointWhenUserAuthenticated(): void
    {
        $this->mockAuth(isGuest: false);

        $expectedRedirect = Mockery::mock(RedirectResponse::class);
        $redirector = $this->mockRedirect();
        $redirector->shouldReceive('action')
            ->with("OAuth2\OAuth2ProviderController@auth")
            ->once()
            ->andReturn($expectedRedirect);

        $result = $this->strategy->getLogin();

        $this->assertSame($expectedRedirect, $result);
    }

    /**
     * Regression test: the old code redirected authenticated users to
     * UserController@getProfile, which broke the OAuth2 flow.
     * Verify that getProfile is never the redirect target.
     */
    public function testGetLoginDoesNotRedirectToProfileWhenUserAuthenticated(): void
    {
        $this->mockAuth(isGuest: false);

        $authEndpointRedirect = Mockery::mock(RedirectResponse::class);
        $redirector = $this->mockRedirect();
        $redirector->shouldReceive('action')
            ->with("OAuth2\OAuth2ProviderController@auth")
            ->once()
            ->andReturn($authEndpointRedirect);

        // This must NOT be called
        $redirector->shouldNotReceive('action')
            ->with("UserController@getProfile");

        $result = $this->strategy->getLogin();

        $this->assertSame($authEndpointRedirect, $result,
            'Authenticated user must be redirected to auth endpoint, not profile');
    }

    /**
     * When the user is a guest (not authenticated), getLogin() should NOT
     * return the early redirect — it should proceed past the guard check.
     * We verify that neither the auth endpoint nor the profile redirect is
     * returned, and that the method proceeds into the login form logic.
     */
    public function testGetLoginProceedsToLoginFormWhenUserIsGuest(): void
    {
        $this->mockAuth(isGuest: true);

        $redirector = $this->mockRedirect();
        // Neither redirect should be called for guests
        $redirector->shouldNotReceive('action')
            ->with("OAuth2\OAuth2ProviderController@auth");
        $redirector->shouldNotReceive('action')
            ->with("UserController@getProfile");

        // The strategy proceeds past the guard check into login_hint processing
        // and memento loading. Since we're using a real LoginHintProcessStrategy
        // without a full request context, we need the Request facade set up.
        $request = Mockery::mock();
        $request->shouldReceive('has')->andReturn(false);
        $request->shouldReceive('query')->andReturn(null);
        $this->app->instance('request', $request);

        // After login_hint processing, the strategy loads the memento.
        // Return null to trigger an exception — we just need to prove
        // the method got past the Auth::guest() check.
        $this->memento_service->shouldReceive('load')->once()->andReturnNull();

        $reachedMemento = false;
        try {
            $this->strategy->getLogin();
        } catch (\Throwable $e) {
            // Expected: proceeds past Auth::guest() check, fails when
            // trying to build from a null memento.
            $reachedMemento = true;
        }

        $this->assertTrue($reachedMemento,
            'Guest path must proceed past Auth::guest() check into memento loading');
    }
}
