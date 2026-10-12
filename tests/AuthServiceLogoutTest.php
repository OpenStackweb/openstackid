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
 * Tests that AuthService::logout() destroys the current session (Session::invalidate():
 * flush + regenerate with destroy=true, so the old session id's data leaves the store)
 * and writes the revocation markers reloadSession() consults.
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

    /**
     * @var array<string, array{0: string, 1: int}> key => [value, ttl] of every setSingleValue() call
     */
    private array $cache_writes = [];

    private function expectSessionInvalidation(): void
    {
        $this->session_mock->shouldReceive('getId')->once()->andReturn('test-session-id');
        $this->cache_writes = [];
        $this->mock_cache_service
            ->method('setSingleValue')
            ->willReturnCallback(function ($key, $value, $ttl = 0) {
                $this->cache_writes[$key] = [$value, $ttl];
                return true;
            });
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

    private function expectSessionInvalidate(): void
    {
        $this->session_mock->shouldReceive('invalidate')->once();
    }

    /**
     * Verify that logout() calls Session::invalidate()
     * when no user is logged in (guest context).
     */
    public function testLogoutFlushesSessionForGuestUser(): void
    {
        $this->mockGuestUser();
        $this->expectSessionInvalidation();
        $this->expectCoreLogoutCalls();
        $this->expectSessionInvalidate();

        $this->service->logout();
    }

    /**
     * Verify that logout() calls Session::invalidate()
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
        $this->expectSessionInvalidate();

        $this->service->logout();
    }

    /**
     * Verify that Session::invalidate() is called AFTER Auth::logout() to ensure
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

        $this->session_mock->shouldReceive('invalidate')->once()->andReturnUsing(function () use (&$call_order) {
            $call_order[] = 'session_invalidate';
        });

        $this->service->logout();

        $this->assertEquals(['auth_logout', 'session_invalidate'], $call_order);
    }

    /**
     * Verify that Session::invalidate() is called AFTER invalidateSession()
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

        $this->mock_cache_service
            ->expects($this->once())
            ->method('setSingleValue')
            ->with('session.revoked.' . hash('sha256', 'original-session-id'), '1', $this->anything());

        $this->session_mock->shouldReceive('invalidate')->once()->andReturnUsing(function () use (&$session_id_captured) {
            $this->assertTrue($session_id_captured, 'Session::invalidate() was called before Session::getId()');
        });

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
        $this->expectSessionInvalidate();

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

        $this->expectSessionInvalidate();

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

        $this->expectSessionInvalidate();

        $this->service->logout();
    }

    /**
     * The session marker is keyed by a hash of the session id, and the
     * user-level "logged out at" marker is written for the authenticated user,
     * both with a TTL (no leaked keys).
     */
    public function testLogoutWritesSessionAndUserRevocationMarkers(): void
    {
        $this->mockAuthenticatedUser();
        $this->expectSessionInvalidation();
        $this->expectCoreLogoutCalls();
        $this->expectSessionInvalidate();

        $this->service->logout();

        $session_key = 'session.revoked.' . hash('sha256', 'test-session-id');
        $this->assertArrayHasKey($session_key, $this->cache_writes);
        $this->assertSame('1', $this->cache_writes[$session_key][0]);
        $this->assertGreaterThan(0, $this->cache_writes[$session_key][1]);

        $this->assertArrayHasKey('user.logged_out_at.42', $this->cache_writes);
        $this->assertEqualsWithDelta(time(), (int)$this->cache_writes['user.logged_out_at.42'][0], 5);
        $this->assertGreaterThan(0, $this->cache_writes['user.logged_out_at.42'][1]);
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

        $this->session_mock->shouldReceive('invalidate')->once()->andReturnUsing(function () use (&$session_flushed) {
            $session_flushed = true;
        });

        $this->service->logout();

        $this->assertTrue($action_logged, 'User action was never logged');
    }
}
