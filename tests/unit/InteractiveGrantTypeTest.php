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

use Auth\User;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Models\OAuth2\Client;
use OAuth2\Models\IClient;
use OAuth2\Models\Principal;
use OAuth2\Models\SecurityContext;
use OAuth2\OAuth2Message;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Requests\OAuth2Request;
use OAuth2\Responses\OAuth2Response;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientJWKSetReader;
use OAuth2\Services\IClientService;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\ITokenService;
use OAuth2\Services\IUserConsentService;
use OAuth2\Strategies\IOAuth2AuthenticationStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;

/**
 * Concrete subclass of InteractiveGrantType for testing.
 * Implements the abstract methods with minimal stubs.
 */
class TestableInteractiveGrantType extends \OAuth2\GrantTypes\InteractiveGrantType
{
    public function __construct(
        IClientService                  $client_service,
        IClientRepository               $client_repository,
        ITokenService                   $token_service,
        ILogService                     $log_service,
        ISecurityContextService         $security_context_service,
        IPrincipalService               $principal_service,
        IAuthService                    $auth_service,
        IUserConsentService             $user_consent_service,
        IApiScopeService                $scope_service,
        IOAuth2AuthenticationStrategy   $auth_strategy,
        IMementoOAuth2SerializerService $memento_service,
        IServerPrivateKeyRepository     $server_private_key_repository,
        IClientJWKSetReader             $jwk_set_reader_service
    ) {
        parent::__construct(
            $client_service,
            $client_repository,
            $token_service,
            $log_service,
            $security_context_service,
            $principal_service,
            $auth_service,
            $user_consent_service,
            $scope_service,
            $auth_strategy,
            $memento_service,
            $server_private_key_repository,
            $jwk_set_reader_service
        );
    }

    public function canHandle(OAuth2Request $request): bool
    {
        return true;
    }

    public function getType()
    {
        return [];
    }

    public function getResponseType()
    {
        return OAuth2Protocol::OAuth2Protocol_ResponseType_Code;
    }

    public function buildTokenRequest(OAuth2Request $request)
    {
        return null;
    }

    public function completeFlow(OAuth2Request $request)
    {
        return null;
    }

    protected function buildResponse(OAuth2AuthorizationRequest $request, $has_former_consent): OAuth2Response
    {
        return Mockery::mock(OAuth2Response::class);
    }

    protected function checkClientTypeAccess(IClient $client): void
    {
        // no-op for tests
    }

    /**
     * Expose the protected handle() method for testing.
     */
    public function publicHandle(OAuth2Request $request): mixed
    {
        return $this->handle($request);
    }
}

/**
 * Class InteractiveGrantTypeTest
 *
 * Tests for InteractiveGrantType focusing on:
 * - Fix A: mustAuthenticateUser logs out user when processUserHint throws
 * - Fix C: id_token_hint is only processed once (isProcessedParam guard)
 * - Regression: the old buggy behavior that redirected to profile
 *
 * @package Tests\unit
 */
class InteractiveGrantTypeTest extends TestCase
{
    private $auth_service;
    private $memento_service;
    private $client_repository;
    private $token_service;
    private $client_service;
    private $log_service;
    private $security_context_service;
    private $principal_service;
    private $user_consent_service;
    private $scope_service;
    private $auth_strategy;
    private $server_private_key_repository;
    private $jwk_set_reader_service;
    private $grant_type;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any resolved facade instances left by prior tests (e.g.,
        // integration tests that bootstrap the full Laravel Application).
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        // Set up a minimal facade root so Log:: calls don't crash.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $app = new Container();
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('debug', 'info', 'warning', 'error', 'critical', 'log')
            ->zeroOrMoreTimes()
            ->andReturnNull();
        $app->instance('log', $logger);
        Facade::setFacadeApplication($app);

        $this->auth_service = Mockery::mock(IAuthService::class);
        $this->memento_service = Mockery::mock(IMementoOAuth2SerializerService::class);
        $this->client_repository = Mockery::mock(IClientRepository::class);
        $this->token_service = Mockery::mock(ITokenService::class);
        $this->client_service = Mockery::mock(IClientService::class);
        $this->log_service = Mockery::mock(ILogService::class);
        $this->security_context_service = Mockery::mock(ISecurityContextService::class);
        $this->principal_service = Mockery::mock(IPrincipalService::class);
        $this->user_consent_service = Mockery::mock(IUserConsentService::class);
        $this->scope_service = Mockery::mock(IApiScopeService::class);
        $this->auth_strategy = Mockery::mock(IOAuth2AuthenticationStrategy::class);
        $this->server_private_key_repository = Mockery::mock(IServerPrivateKeyRepository::class);
        $this->jwk_set_reader_service = Mockery::mock(IClientJWKSetReader::class);

        // Suppress log calls
        $this->log_service->shouldReceive('debug_msg')->andReturnNull();
        $this->log_service->shouldReceive('warning')->andReturnNull();
        $this->log_service->shouldReceive('warning_msg')->andReturnNull();
        $this->log_service->shouldReceive('error')->andReturnNull();
        $this->log_service->shouldReceive('error_msg')->andReturnNull();
        $this->log_service->shouldReceive('info')->andReturnNull();

        // Default principal service mock (used by shouldForceReLogin)
        $principal = new Principal();
        $principal->setState([0, time(), '']);
        $this->principal_service->shouldReceive('get')->andReturn($principal)->byDefault();

        $this->grant_type = new TestableInteractiveGrantType(
            $this->client_service,
            $this->client_repository,
            $this->token_service,
            $this->log_service,
            $this->security_context_service,
            $this->principal_service,
            $this->auth_service,
            $this->user_consent_service,
            $this->scope_service,
            $this->auth_strategy,
            $this->memento_service,
            $this->server_private_key_repository,
            $this->jwk_set_reader_service
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
     * Build a valid OAuth2AuthenticationRequest (OIDC) with the given extra params merged in.
     */
    private function buildOIDCRequest(array $extra = []): OAuth2AuthenticationRequest
    {
        $params = array_merge([
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            OAuth2Protocol::OAuth2Protocol_ClientId => 'test-client-id',
            OAuth2Protocol::OAuth2Protocol_RedirectUri => 'https://client.example.com/callback',
            OAuth2Protocol::OAuth2Protocol_Scope => 'openid profile',
            OAuth2Protocol::OAuth2Protocol_State => 'random-state',
            OAuth2Protocol::OAuth2Protocol_Nonce => 'random-nonce',
        ], $extra);

        $msg = new OAuth2Message($params);
        $auth_request = new OAuth2AuthorizationRequest($msg);
        return new OAuth2AuthenticationRequest($auth_request);
    }

    /**
     * Set up common mocks for a client that passes all validation checks.
     */
    private function setupValidClient(): Client
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('isActive')->andReturn(true);
        $client->shouldReceive('isLocked')->andReturn(false);
        $client->shouldReceive('getApplicationName')->andReturn('Test App');
        $client->shouldReceive('getId')->andReturn(1);
        $client->shouldReceive('getClientId')->andReturn('test-client-id');
        $client->shouldReceive('isUriAllowed')->andReturn(true);
        $client->shouldReceive('isScopeAllowed')->andReturn(true);
        $client->shouldReceive('getDefaultMaxAge')->andReturn(0);
        $client->shouldReceive('getMaxAllowedUserSessions')->andReturn(0);

        $this->client_repository
            ->shouldReceive('getClientById')
            ->with('test-client-id')
            ->andReturn($client);

        return $client;
    }

    /**
     * Set up mocks for a logged-in user who has already authenticated.
     */
    private function setupLoggedInUser(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn(42);

        $this->auth_service->shouldReceive('isUserLogged')->andReturn(true);
        $this->auth_service->shouldReceive('getCurrentUser')->andReturn($user);
        $this->auth_service->shouldReceive('getUserAuthenticationResponse')
            ->andReturn(IAuthService::AuthenticationResponse_None);

        // principal_service->get() is called by shouldForceReLogin()
        $principal = new Principal();
        $principal->setState([42, time(), 'test-opbs']);
        $this->principal_service->shouldReceive('get')->andReturn($principal);

        return $user;
    }

    /**
     * Set up security context mock.
     */
    private function setupSecurityContext(): void
    {
        $security_context = new SecurityContext();

        $this->security_context_service
            ->shouldReceive('get')
            ->andReturn($security_context);

        $this->security_context_service
            ->shouldReceive('save')
            ->andReturnNull();
    }

    /**
     * Allow the auth_service and memento_service cleanup calls that happen
     * in the outer catch block of handle() or in normal flow cleanup.
     */
    private function allowCleanupCalls(): void
    {
        $this->auth_service->shouldReceive('clearUserAuthorizationResponse')->andReturnNull();
        $this->auth_service->shouldReceive('clearUserAuthenticationResponse')->andReturnNull();
        $this->memento_service->shouldReceive('forget')->andReturnNull();
    }

    // -----------------------------------------------------------------------
    // Fix A: mustAuthenticateUser logs user out when processUserHint throws
    // -----------------------------------------------------------------------

    /**
     * When processUserHint throws a generic exception during handle(),
     * the user MUST be logged out before being redirected to login.
     *
     * This is the fix for the bug where the user stayed authenticated,
     * causing OAuth2LoginStrategy::getLogin() to redirect to profile
     * instead of showing the login page.
     */
    public function testHandleLogsOutUserWhenProcessUserHintThrowsException(): void
    {
        $request = $this->buildOIDCRequest([
            OAuth2Protocol::OAuth2Protocol_LoginHint => 'nonexistent@example.com',
        ]);

        $client = $this->setupValidClient();
        $this->setupSecurityContext();
        $this->allowCleanupCalls();

        // The user is currently logged in
        $this->auth_service->shouldReceive('isUserLogged')->andReturn(true);
        $this->auth_service->shouldReceive('getUserAuthenticationResponse')
            ->andReturn(IAuthService::AuthenticationResponse_None);

        // getUserByUsername will throw, simulating a DB error or similar failure
        $this->auth_service->shouldReceive('getUserByUsername')
            ->with('nonexistent@example.com')
            ->andThrow(new Exception('User lookup failed'));

        // FIX A: logout(false) MUST be called before redirecting to login
        $this->auth_service->shouldReceive('logout')
            ->with(false)
            ->once();

        // After the exception, the flow should save the memento and redirect to login
        $this->memento_service->shouldReceive('serialize')->once();

        $login_redirect = 'login-redirect-response';
        $this->auth_strategy->shouldReceive('doLogin')
            ->once()
            ->andReturn($login_redirect);

        $result = $this->grant_type->publicHandle($request);

        $this->assertEquals($login_redirect, $result);
    }

    /**
     * Regression test: Without fix A, the old code did NOT call logout()
     * when processUserHint threw, leaving the user authenticated.
     * This verifies the fix is in place by asserting logout IS called.
     */
    public function testRegressionProcessUserHintExceptionWithoutLogoutCausedProfileRedirect(): void
    {
        // Simulate the exact scenario that caused the bug:
        // 1. User is logged in (just submitted consent)
        // 2. processUserHint throws on re-entry (e.g., login_hint lookup fails)
        // 3. Without logout, doLogin -> getLogin() -> user is logged in -> profile redirect

        $request = $this->buildOIDCRequest([
            OAuth2Protocol::OAuth2Protocol_LoginHint => 'bad-hint',
        ]);

        $client = $this->setupValidClient();
        $this->setupSecurityContext();
        $this->allowCleanupCalls();

        $this->auth_service->shouldReceive('isUserLogged')->andReturn(true);
        $this->auth_service->shouldReceive('getUserAuthenticationResponse')
            ->andReturn(IAuthService::AuthenticationResponse_None);

        // login_hint is not a valid email, so unwrapUserId is called
        $this->auth_service->shouldReceive('unwrapUserId')
            ->with('bad-hint')
            ->andThrow(new Exception('Invalid user identifier'));

        // The critical assertion: logout MUST be called
        $this->auth_service->shouldReceive('logout')
            ->with(false)
            ->once();

        $this->memento_service->shouldReceive('serialize')->once();
        $this->auth_strategy->shouldReceive('doLogin')->once()->andReturn('login-response');

        $result = $this->grant_type->publicHandle($request);

        $this->assertEquals('login-response', $result);
    }

    // -----------------------------------------------------------------------
    // Fix C: id_token_hint processed only once
    // -----------------------------------------------------------------------

    /**
     * After id_token_hint is processed, subsequent passes through handle()
     * must NOT re-process it (the isProcessedParam guard must work).
     */
    public function testIdTokenHintIsNotReprocessedOnSubsequentPasses(): void
    {
        // Build a request where login_hint and id_token_hint are both already processed
        $request = $this->buildOIDCRequest([
            OAuth2Protocol::OAuth2Protocol_LoginHint => 'user@example.com',
            OAuth2Protocol::OAuth2Protocol_IDTokenHint => 'some.jwt.token',
        ]);

        // Mark both as already processed (simulating previous passes)
        $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_LoginHint);
        $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_IDTokenHint);

        $client = $this->setupValidClient();
        $user = $this->setupLoggedInUser();
        $this->setupSecurityContext();

        $this->token_service->shouldReceive('canCreateAccessToken')
            ->with($user, $client)
            ->andReturn(true);

        // User has already consented
        $this->auth_service->shouldReceive('getUserAuthorizationResponse')
            ->andReturn(IAuthService::AuthorizationResponse_AllowOnce);
        $this->auth_service->shouldReceive('registerRPLogin')->once();
        $this->auth_service->shouldReceive('clearUserAuthorizationResponse')->once();

        $user->shouldReceive('findFirstConsentByClientAndScopes')
            ->andReturn(null);
        $this->user_consent_service->shouldReceive('addUserConsent')->once();

        $this->memento_service->shouldReceive('forget')->once();

        // The key assertion: user-lookup and JWT methods should NOT be called
        // because both hints are already processed.
        $this->auth_service->shouldNotReceive('getUserByUsername');
        $this->auth_service->shouldNotReceive('unwrapUserId');
        $this->auth_service->shouldNotReceive('reloadSession');

        $result = $this->grant_type->publicHandle($request);

        // Should get an OAuth2Response (successful authorization), not a redirect to login
        $this->assertInstanceOf(OAuth2Response::class, $result);
    }

    /**
     * Verify that the isProcessedParam / markParamAsProcessed mechanism
     * works correctly for id_token_hint through the memento round-trip.
     */
    public function testIdTokenHintProcessedParamPersistsThroughMemento(): void
    {
        $request = $this->buildOIDCRequest([
            OAuth2Protocol::OAuth2Protocol_LoginHint => 'user@example.com',
            OAuth2Protocol::OAuth2Protocol_IDTokenHint => 'some.jwt.token',
        ]);

        // Mark login_hint as already processed
        $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_LoginHint);

        // id_token_hint is NOT yet processed
        $this->assertFalse(
            $request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_IDTokenHint)
        );

        // Mark it as processed (simulating what the code does after first pass)
        $request->markParamAsProcessed(OAuth2Protocol::OAuth2Protocol_IDTokenHint);

        // Verify it IS now processed
        $this->assertTrue(
            $request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_IDTokenHint)
        );

        // Verify it survives a memento round-trip
        $memento = $request->getMessage()->createMemento();
        $rebuilt_msg = OAuth2Message::buildFromMemento($memento);
        $rebuilt_auth = new OAuth2AuthorizationRequest($rebuilt_msg);
        $rebuilt_request = new OAuth2AuthenticationRequest($rebuilt_auth);

        $this->assertTrue(
            $rebuilt_request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_IDTokenHint),
            'id_token_hint processed flag must survive memento serialization round-trip'
        );
        $this->assertTrue(
            $rebuilt_request->isProcessedParam(OAuth2Protocol::OAuth2Protocol_LoginHint),
            'login_hint processed flag must survive memento serialization round-trip'
        );
    }

    // -----------------------------------------------------------------------
    // Normal flow: consent accepted -> successful authorization
    // -----------------------------------------------------------------------

    /**
     * When the user has already consented (AllowOnce), handle() should
     * build a successful response and clear the memento.
     */
    public function testHandleReturnsResponseWhenUserConsented(): void
    {
        $request = $this->buildOIDCRequest();

        $client = $this->setupValidClient();
        $user = $this->setupLoggedInUser();
        $this->setupSecurityContext();

        $this->token_service->shouldReceive('canCreateAccessToken')
            ->with($user, $client)
            ->andReturn(true);

        $this->auth_service->shouldReceive('getUserAuthorizationResponse')
            ->andReturn(IAuthService::AuthorizationResponse_AllowOnce);
        $this->auth_service->shouldReceive('registerRPLogin')->once();
        $this->auth_service->shouldReceive('clearUserAuthorizationResponse')->once();

        $user->shouldReceive('findFirstConsentByClientAndScopes')
            ->andReturn(null);
        $this->user_consent_service->shouldReceive('addUserConsent')->once();

        $this->memento_service->shouldReceive('forget')->once();

        $result = $this->grant_type->publicHandle($request);

        $this->assertInstanceOf(OAuth2Response::class, $result);
    }

    /**
     * When the user is not logged in and no hints are provided,
     * handle() should redirect to the login page.
     */
    public function testHandleRedirectsToLoginWhenUserNotAuthenticated(): void
    {
        $request = $this->buildOIDCRequest();

        $client = $this->setupValidClient();
        $this->setupSecurityContext();
        $this->allowCleanupCalls();

        // User is NOT logged in
        $this->auth_service->shouldReceive('isUserLogged')->andReturn(false);
        $this->auth_service->shouldReceive('getUserAuthenticationResponse')
            ->andReturn(IAuthService::AuthenticationResponse_None);

        $this->memento_service->shouldReceive('serialize')->once();

        $login_redirect = 'login-redirect';
        $this->auth_strategy->shouldReceive('doLogin')
            ->once()
            ->andReturn($login_redirect);

        $result = $this->grant_type->publicHandle($request);

        $this->assertEquals($login_redirect, $result);
    }

    /**
     * When prompt=consent and user has no prior consent, handle()
     * should redirect to the consent page.
     */
    public function testHandleRedirectsToConsentWhenPromptConsentSet(): void
    {
        $request = $this->buildOIDCRequest([
            OAuth2Protocol::OAuth2Protocol_Prompt => OAuth2Protocol::OAuth2Protocol_Prompt_Consent,
        ]);

        $client = $this->setupValidClient();
        $user = $this->setupLoggedInUser();
        $this->setupSecurityContext();
        $this->allowCleanupCalls();

        $this->token_service->shouldReceive('canCreateAccessToken')
            ->with($user, $client)
            ->andReturn(true);

        $this->auth_service->shouldReceive('getUserAuthorizationResponse')
            ->andReturn(IAuthService::AuthorizationResponse_None);

        $user->shouldReceive('findFirstConsentByClientAndScopes')
            ->andReturn(null);

        $this->memento_service->shouldReceive('serialize')->once();

        $consent_redirect = 'consent-redirect';
        $this->auth_strategy->shouldReceive('doConsent')
            ->once()
            ->andReturn($consent_redirect);

        $result = $this->grant_type->publicHandle($request);

        $this->assertEquals($consent_redirect, $result);
    }
}
