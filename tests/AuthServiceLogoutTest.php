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
use Utils\Services\IAuthService;

/**
 * Class AuthServiceLogoutTest
 * Tests that AuthService::logout() properly flushes all session data
 * and regenerates the session ID, fixing the incomplete session cleanup
 * that previously required callers to manually call Session::flush().
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class AuthServiceLogoutTest extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    private AuthService $service;

    private $mock_principal_service;
    private $mock_user_action_service;
    private $mock_cache_service;
    private $mock_security_context_service;

    // Facade aliases
    private $auth_mock;
    private $session_mock;
    private $config_mock;
    private $cookie_mock;
    private $crypt_mock;
    private $log_mock;

    protected function setUp(): void
    {
        parent::setUp();

        $mock_user_repository = $this->createMock(IUserRepository::class);
        $mock_otp_repository = $this->createMock(IOAuth2OTPRepository::class);
        $this->mock_principal_service = $this->createMock(IPrincipalService::class);
        $mock_user_service = $this->createMock(IUserService::class);
        $this->mock_user_action_service = $this->createMock(IUserActionService::class);
        $this->mock_cache_service = $this->createMock(ICacheService::class);
        $mock_auth_user_service = $this->createMock(IAuthUserService::class);
        $this->mock_security_context_service = $this->createMock(ISecurityContextService::class);
        $mock_tx_service = $this->createMock(ITransactionService::class);

        // Mock facades using Mockery alias (no Laravel app container needed)
        $this->auth_mock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $this->session_mock = Mockery::mock('alias:Illuminate\Support\Facades\Session');
        $this->config_mock = Mockery::mock('alias:Illuminate\Support\Facades\Config');
        $this->cookie_mock = Mockery::mock('alias:Illuminate\Support\Facades\Cookie');
        $this->crypt_mock = Mockery::mock('alias:Illuminate\Support\Facades\Crypt');
        $this->log_mock = Mockery::mock('alias:Illuminate\Support\Facades\Log');

        // Log calls are always allowed
        $this->log_mock->shouldReceive('debug')->zeroOrMoreTimes();
        $this->log_mock->shouldReceive('debug_msg')->zeroOrMoreTimes();

        $this->service = new AuthService(
            $mock_user_repository,
            $mock_otp_repository,
            $this->mock_principal_service,
            $mock_user_service,
            $this->mock_user_action_service,
            $this->mock_cache_service,
            $mock_auth_user_service,
            $this->mock_security_context_service,
            $mock_tx_service
        );
    }

    private function mockGuestUser(): void
    {
        $this->auth_mock->shouldReceive('user')->andReturn(null);
        $this->auth_mock->shouldReceive('check')->andReturn(false);
    }

    private function mockAuthenticatedUser(): Mockery\MockInterface
    {
        $user = Mockery::mock('Auth\User');
        $user->shouldReceive('getId')->andReturn(42);
        $this->auth_mock->shouldReceive('user')->andReturn($user);
        $this->auth_mock->shouldReceive('check')->andReturn(true);
        return $user;
    }

    private function expectSessionInvalidation(): void
    {
        $this->session_mock->shouldReceive('getId')->once()->andReturn('test-session-id');
        $this->crypt_mock->shouldReceive('encrypt')->once()->with('test-session-id')->andReturn('encrypted-session-id');
        $this->mock_cache_service
            ->expects($this->once())
            ->method('addSingleValue')
            ->with('encrypted-session-idinvalid', 'encrypted-session-id');
    }

    private function expectCoreLogoutCalls(bool $clear_security_ctx = true): void
    {
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->auth_mock->shouldReceive('logout')->once();

        if ($clear_security_ctx) {
            $this->mock_security_context_service->expects($this->once())->method('clear');
        } else {
            $this->mock_security_context_service->expects($this->never())->method('clear');
        }

        $this->config_mock->shouldReceive('get')->with('session.path')->andReturn('/');
        $this->config_mock->shouldReceive('get')->with('session.domain')->andReturn('.example.com');
        $this->cookie_mock->shouldReceive('queue')->once();
    }

    private function expectSessionFlushAndRegenerate(): void
    {
        $this->session_mock->shouldReceive('flush')->once();
        $this->session_mock->shouldReceive('regenerate')->once();
    }

    /**
     * Verify that logout() calls Session::flush() and Session::regenerate()
     * when no user is logged in (guest context).
     */
    public function testLogoutFlushesSessionForGuestUser(): void
    {
        $this->mockGuestUser();
        $this->expectSessionInvalidation();
        $this->expectCoreLogoutCalls();
        $this->expectSessionFlushAndRegenerate();

        $this->service->logout();
    }

    /**
     * Verify that logout() calls Session::flush() and Session::regenerate()
     * when an authenticated user is logged in.
     */
    public function testLogoutFlushesSessionForAuthenticatedUser(): void
    {
        $this->mockAuthenticatedUser();
        $this->mock_user_action_service
            ->expects($this->once())
            ->method('addUserAction');

        $this->expectSessionInvalidation();
        $this->expectCoreLogoutCalls();
        $this->expectSessionFlushAndRegenerate();

        $this->service->logout();
    }

    /**
     * Verify that Session::flush() is called AFTER Auth::logout() to ensure
     * the Laravel auth guard has already cleared its state before the session
     * is destroyed. This ordering prevents Auth::logout() from operating
     * on an empty session.
     */
    public function testLogoutCallsFlushAfterAuthLogout(): void
    {
        $this->mockGuestUser();
        $this->expectSessionInvalidation();
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_security_context_service->expects($this->once())->method('clear');

        $this->config_mock->shouldReceive('get')->with('session.path')->andReturn('/');
        $this->config_mock->shouldReceive('get')->with('session.domain')->andReturn('.example.com');
        $this->cookie_mock->shouldReceive('queue')->once();

        $call_order = [];

        $this->auth_mock->shouldReceive('logout')->once()->andReturnUsing(function () use (&$call_order) {
            $call_order[] = 'auth_logout';
        });

        $this->session_mock->shouldReceive('flush')->once()->andReturnUsing(function () use (&$call_order) {
            $call_order[] = 'session_flush';
        });

        $this->session_mock->shouldReceive('regenerate')->once()->andReturnUsing(function () use (&$call_order) {
            $call_order[] = 'session_regenerate';
        });

        $this->service->logout();

        $this->assertEquals(['auth_logout', 'session_flush', 'session_regenerate'], $call_order);
    }

    /**
     * Verify that Session::flush() is called AFTER invalidateSession()
     * captures the session ID. If flush happened first, the session ID
     * would be lost and the cache blacklist entry would be wrong.
     */
    public function testLogoutCapturesSessionIdBeforeFlush(): void
    {
        $this->mockGuestUser();
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_security_context_service->expects($this->once())->method('clear');
        $this->auth_mock->shouldReceive('logout')->once();

        $this->config_mock->shouldReceive('get')->with('session.path')->andReturn('/');
        $this->config_mock->shouldReceive('get')->with('session.domain')->andReturn('.example.com');
        $this->cookie_mock->shouldReceive('queue')->once();

        $session_id_captured = false;

        $this->session_mock->shouldReceive('getId')->once()->andReturnUsing(function () use (&$session_id_captured) {
            $session_id_captured = true;
            return 'original-session-id';
        });

        $this->crypt_mock->shouldReceive('encrypt')->once()->with('original-session-id')->andReturn('encrypted-id');
        $this->mock_cache_service
            ->expects($this->once())
            ->method('addSingleValue')
            ->with('encrypted-idinvalid', 'encrypted-id');

        $this->session_mock->shouldReceive('flush')->once()->andReturnUsing(function () use (&$session_id_captured) {
            $this->assertTrue($session_id_captured, 'Session::flush() was called before Session::getId()');
        });

        $this->session_mock->shouldReceive('regenerate')->once();

        $this->service->logout();
    }

    /**
     * Verify that when clear_security_ctx is false, the security context
     * is NOT cleared but session flush still happens.
     */
    public function testLogoutWithoutSecurityContextClearStillFlushesSession(): void
    {
        $this->mockGuestUser();
        $this->expectSessionInvalidation();
        $this->expectCoreLogoutCalls(clear_security_ctx: false);
        $this->expectSessionFlushAndRegenerate();

        $this->service->logout(clear_security_ctx: false);
    }

    /**
     * Verify that the rps cookie is queued for deletion during logout.
     * This ensures relying party tracking is cleaned up.
     */
    public function testLogoutDeletesRpsCookie(): void
    {
        $this->mockGuestUser();
        $this->expectSessionInvalidation();
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_security_context_service->expects($this->once())->method('clear');
        $this->auth_mock->shouldReceive('logout')->once();

        $this->config_mock->shouldReceive('get')->with('session.path')->andReturn('/test-path');
        $this->config_mock->shouldReceive('get')->with('session.domain')->andReturn('.test-domain.com');

        $this->cookie_mock->shouldReceive('queue')->once()->with(
            IAuthService::LOGGED_RELAYING_PARTIES_COOKIE_NAME,
            null,
            -2628000,
            '/test-path',
            '.test-domain.com',
            true,
            true,
            false,
            'none'
        );

        $this->expectSessionFlushAndRegenerate();

        $this->service->logout();
    }

    /**
     * Verify that principal_service->clear() is called during logout,
     * which removes the op_bs cookie and session keys (user_id, auth_time, opbs).
     */
    public function testLogoutClearsPrincipalService(): void
    {
        $this->mockGuestUser();
        $this->expectSessionInvalidation();

        $this->mock_principal_service
            ->expects($this->once())
            ->method('clear');

        $this->mock_security_context_service->expects($this->once())->method('clear');
        $this->auth_mock->shouldReceive('logout')->once();

        $this->config_mock->shouldReceive('get')->with('session.path')->andReturn('/');
        $this->config_mock->shouldReceive('get')->with('session.domain')->andReturn('.example.com');
        $this->cookie_mock->shouldReceive('queue')->once();

        $this->expectSessionFlushAndRegenerate();

        $this->service->logout();
    }

    /**
     * Verify that user action logging captures the user ID and IP before
     * session data is destroyed.
     */
    public function testLogoutLogsUserActionBeforeSessionDestroyed(): void
    {
        $this->mockAuthenticatedUser();

        $action_logged = false;
        $session_flushed = false;

        $user_action_mock = Mockery::mock('Models\UserAction');
        $this->mock_user_action_service
            ->expects($this->once())
            ->method('addUserAction')
            ->willReturnCallback(function () use (&$action_logged, &$session_flushed, $user_action_mock) {
                $this->assertFalse($session_flushed, 'User action must be logged before session flush');
                $action_logged = true;
                return $user_action_mock;
            });

        $this->expectSessionInvalidation();
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_security_context_service->expects($this->once())->method('clear');
        $this->auth_mock->shouldReceive('logout')->once();

        $this->config_mock->shouldReceive('get')->with('session.path')->andReturn('/');
        $this->config_mock->shouldReceive('get')->with('session.domain')->andReturn('.example.com');
        $this->cookie_mock->shouldReceive('queue')->once();

        $this->session_mock->shouldReceive('flush')->once()->andReturnUsing(function () use (&$session_flushed) {
            $session_flushed = true;
        });
        $this->session_mock->shouldReceive('regenerate')->once();

        $this->service->logout();

        $this->assertTrue($action_logged, 'User action was never logged');
    }
}
