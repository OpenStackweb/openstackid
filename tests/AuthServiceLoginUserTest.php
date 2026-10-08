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

use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use Auth\AuthService;
use Auth\Repositories\IUserRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OpenId\Services\IUserService;
use App\Services\Auth\IUserService as IAuthUserService;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Services\IUserActionService;
use Utils\Db\ITransactionService;
use Utils\Services\ICacheService;

/**
 * Class AuthServiceLoginUserTest
 *
 * Tests that AuthService::loginUser() calls Auth::login() BEFORE
 * PrincipalService::register(). Laravel's SessionGuard::login() already
 * regenerates the session ID internally (session->migrate(true)), closing
 * the pre-auth session-fixation window (SDS idp-mfa.md §9.3) - no explicit
 * Session::regenerate() call is needed. What matters is ordering:
 * register() hashes the CURRENT session ID into op_browser_state (used for
 * OIDC Session Management). If it ran BEFORE Auth::login(), that hash would
 * be computed from the id Auth::login() is about to invalidate, desyncing
 * the check-session iframe contract for any relying party using it.
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class AuthServiceLoginUserTest extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    private AuthService $service;

    private $mock_principal_service;

    // Facade aliases
    private $auth_mock;

    protected function setUp(): void
    {
        parent::setUp();

        $mock_user_repository = $this->createMock(IUserRepository::class);
        $mock_otp_repository = $this->createMock(IOAuth2OTPRepository::class);
        $this->mock_principal_service = $this->createMock(IPrincipalService::class);
        $mock_user_service = $this->createMock(IUserService::class);
        $mock_user_action_service = $this->createMock(IUserActionService::class);
        $mock_cache_service = $this->createMock(ICacheService::class);
        $mock_auth_user_service = $this->createMock(IAuthUserService::class);
        $mock_security_context_service = $this->createMock(ISecurityContextService::class);
        $mock_tx_service = $this->createMock(ITransactionService::class);

        $this->auth_mock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');

        $log_mock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $log_mock->shouldReceive('debug')->zeroOrMoreTimes();

        $this->service = new AuthService(
            $mock_user_repository,
            $mock_otp_repository,
            $this->mock_principal_service,
            $mock_user_service,
            $mock_user_action_service,
            $mock_cache_service,
            $mock_auth_user_service,
            $mock_security_context_service,
            $mock_tx_service
        );
    }

    private function mockLoggableUser(): Mockery\MockInterface
    {
        $user = Mockery::mock('Auth\User');
        $user->shouldReceive('canLogin')->andReturn(true);
        $user->shouldReceive('getId')->andReturn(42);
        return $user;
    }

    public function testLoginUserCallsAuthLoginBeforeRegisteringPrincipal(): void
    {
        $user = $this->mockLoggableUser();
        $call_order = [];

        $this->auth_mock->shouldReceive('login')->once()->andReturnUsing(function () use (&$call_order) {
            $call_order[] = 'auth_login';
        });

        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_principal_service->expects($this->once())->method('register')->willReturnCallback(
            function () use (&$call_order) {
                $call_order[] = 'principal_register';
            }
        );

        $this->service->loginUser($user, false);

        $this->assertSame(
            ['auth_login', 'principal_register'],
            $call_order,
            'Auth::login() must run before register() computes op_browser_state from the session ID - ' .
            'Auth::login() regenerates the session ID internally, so register() must use the post-login id'
        );
    }
}
