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

use App\libs\OAuth2\Exceptions\ReloadSessionException;
use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use Auth\AuthService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OAuth2\Models\Principal;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OpenId\Services\IUserService;
use App\Services\Auth\IUserService as IAuthUserService;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;
use Services\IUserActionService;
use Utils\Db\ITransactionService;
use Utils\Services\ICacheService;

/**
 * Class AuthServiceReloadSessionTest
 *
 * Covers 3 findings raised on PR #158 (session-hint fallback authentication
 * in AuthService::reloadSession()):
 *
 * - Both fallback Auth::login() calls must honor User::canLogin(), same as
 *   every other login entry point in AuthService - getUserById() does not
 *   filter by account status on its own.
 * - Auth::login() alone leaves the IDP's own principal state (user_id /
 *   auth_time / op_browser_state) unset; every other login path pairs it
 *   with principal_service->register().
 * - A non-ReloadSessionException failure inside the try block (e.g. a DB
 *   error) must still restore the caller's former session before
 *   propagating, and the ReloadSessionException catch block must rethrow
 *   rather than return silently when there is no $user_id to fall back to.
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class AuthServiceReloadSessionTest extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    private AuthService $service;

    private $mock_user_repository;
    private $mock_principal_service;
    private $mock_cache_service;

    private $auth_mock;
    private $session_mock;
    private $crypt_mock;
    private $log_mock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock_user_repository = $this->createMock(IUserRepository::class);
        $mock_otp_repository = $this->createMock(IOAuth2OTPRepository::class);
        $this->mock_principal_service = $this->createMock(IPrincipalService::class);
        $mock_user_service = $this->createMock(IUserService::class);
        $mock_user_action_service = $this->createMock(IUserActionService::class);
        $this->mock_cache_service = $this->createMock(ICacheService::class);
        $mock_auth_user_service = $this->createMock(IAuthUserService::class);
        $mock_security_context_service = $this->createMock(ISecurityContextService::class);
        $mock_tx_service = $this->createMock(ITransactionService::class);

        $this->auth_mock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $this->session_mock = Mockery::mock('alias:Illuminate\Support\Facades\Session');
        $this->crypt_mock = Mockery::mock('alias:Illuminate\Support\Facades\Crypt');
        $this->log_mock = Mockery::mock('alias:Illuminate\Support\Facades\Log');

        $this->log_mock->shouldReceive('debug')->zeroOrMoreTimes();
        $this->log_mock->shouldReceive('debug_msg')->zeroOrMoreTimes();
        $this->log_mock->shouldReceive('warning')->zeroOrMoreTimes();

        $this->session_mock->shouldReceive('start')->zeroOrMoreTimes();

        $this->service = new AuthService(
            $this->mock_user_repository,
            $mock_otp_repository,
            $this->mock_principal_service,
            $mock_user_service,
            $mock_user_action_service,
            $this->mock_cache_service,
            $mock_auth_user_service,
            $mock_security_context_service,
            $mock_tx_service
        );
    }

    private function mockUser(int $id, bool $can_login): Mockery\MockInterface
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('canLogin')->andReturn($can_login);
        return $user;
    }

    // -----------------------------------------------------------------------
    // Cache-miss fallback: reloadSession() falls straight to Auth::login($user)
    // when the jti isn't cached at all.
    // -----------------------------------------------------------------------

    public function testCacheMissFallbackRejectsUserThatCannotLogin(): void
    {
        $this->mock_cache_service->method('getSingleValue')->with('jti-1')->willReturn(null);

        $user = $this->mockUser(42, can_login: false);
        $this->mock_user_repository->method('getByIdWithGroups')->with(42)->willReturn($user);

        $this->session_mock->shouldReceive('getId')->once()->andReturn('former-session-id');
        $this->auth_mock->shouldNotReceive('login');
        $this->mock_principal_service->expects($this->never())->method('register');

        $this->expectException(ReloadSessionException::class);

        $this->service->reloadSession('jti-1', '42');
    }

    public function testCacheMissFallbackRegistersPrincipalOnSuccess(): void
    {
        $this->mock_cache_service->method('getSingleValue')->with('jti-1')->willReturn(null);

        $user = $this->mockUser(42, can_login: true);
        $this->mock_user_repository->method('getByIdWithGroups')->with(42)->willReturn($user);

        $this->session_mock->shouldReceive('getId')->once()->andReturn('former-session-id');
        $this->auth_mock->shouldReceive('login')->once()->with($user);

        // The principal must be registered with the auth_time the hint attested,
        // not with "now" - the user did not authenticate on this request.
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_principal_service->expects($this->once())->method('register')->with(42, 1700000000);

        $this->service->reloadSession('jti-1', '42', 1700000000);
    }

    // -----------------------------------------------------------------------
    // catch(ReloadSessionException): the cached session resumes but has no
    // live principal, so the fallback by $user_id kicks in.
    // -----------------------------------------------------------------------

    private function mockFailedSessionResume(): void
    {
        $this->mock_cache_service->method('getSingleValue')->with('jti-1')->willReturn('encrypted-session-id');
        $this->mock_cache_service->method('exists')->with('encrypted-session-idinvalid')->willReturn(false);

        $this->crypt_mock->shouldReceive('decrypt')->with('encrypted-session-id')->andReturn('decrypted-session-id');
        $this->session_mock->shouldReceive('setId')->with('decrypted-session-id')->zeroOrMoreTimes();
        $this->auth_mock->shouldReceive('check')->andReturn(false);

        $principal = new Principal();
        $principal->setState([0, time(), '']);
        $this->mock_principal_service->method('get')->willReturn($principal);
    }

    public function testCatchFallbackRejectsUserThatCannotLogin(): void
    {
        $this->mockFailedSessionResume();

        $this->session_mock->shouldReceive('getId')->once()->andReturn('former-session-id');
        $this->session_mock->shouldReceive('setId')->with('former-session-id')->once();

        $user = $this->mockUser(99, can_login: false);
        // getByIdWithGroups is called twice: once with 0 (inside the try
        // block, from the resumed-but-empty session's principal - throws
        // and reaches the catch), then with 99 (the $user_id fallback).
        $this->mock_user_repository->method('getByIdWithGroups')->willReturnMap([
            [0, null],
            [99, $user],
        ]);

        $this->auth_mock->shouldNotReceive('login');
        $this->mock_principal_service->expects($this->never())->method('register');

        $this->expectException(ReloadSessionException::class);

        $this->service->reloadSession('jti-1', '99');
    }

    public function testCatchFallbackRegistersPrincipalOnSuccess(): void
    {
        $this->mockFailedSessionResume();

        $this->session_mock->shouldReceive('getId')->once()->andReturn('former-session-id');
        $this->session_mock->shouldReceive('setId')->with('former-session-id')->once();

        $user = $this->mockUser(99, can_login: true);
        $this->mock_user_repository->method('getByIdWithGroups')->willReturnMap([
            [0, null],
            [99, $user],
        ]);

        $this->auth_mock->shouldReceive('login')->once()->with($user);
        // Same contract as the cache-miss branch: register the attested auth_time.
        $this->mock_principal_service->expects($this->once())->method('clear');
        $this->mock_principal_service->expects($this->once())->method('register')->with(99, 1700000000);

        $this->service->reloadSession('jti-1', '99', 1700000000);
    }

    /**
     * No $user_id fallback was provided: the failed resume must propagate,
     * not return as if reloadSession() had succeeded.
     */
    public function testCatchRethrowsWhenNoFallbackUserIdProvided(): void
    {
        $this->mockFailedSessionResume();
        $this->mock_user_repository->method('getByIdWithGroups')->with(0)->willReturn(null);

        $this->session_mock->shouldReceive('getId')->once()->andReturn('former-session-id');
        $this->session_mock->shouldReceive('setId')->with('former-session-id')->once();

        $this->auth_mock->shouldNotReceive('login');

        $this->expectException(ReloadSessionException::class);

        $this->service->reloadSession('jti-1');
    }

    // -----------------------------------------------------------------------
    // A non-ReloadSessionException failure (e.g. a DB error) must still
    // restore the caller's former session before propagating.
    // -----------------------------------------------------------------------

    public function testNonReloadSessionExceptionRestoresFormerSessionAndRethrows(): void
    {
        $this->mock_cache_service->method('getSingleValue')->with('jti-1')->willReturn('encrypted-session-id');
        $this->mock_cache_service->method('exists')->with('encrypted-session-idinvalid')->willReturn(false);

        $this->crypt_mock->shouldReceive('decrypt')->with('encrypted-session-id')->andReturn('decrypted-session-id');
        $this->session_mock->shouldReceive('setId')->with('decrypted-session-id')->once();
        $this->auth_mock->shouldReceive('check')->andReturn(false);

        $principal = new Principal();
        $principal->setState([7, time(), 'opbs']);
        $this->mock_principal_service->method('get')->willReturn($principal);

        // A DB-layer failure, NOT a ReloadSessionException.
        $this->mock_user_repository
            ->method('getByIdWithGroups')
            ->with(7)
            ->willThrowException(new RuntimeException('DB is down'));

        $this->session_mock->shouldReceive('getId')->once()->andReturn('former-session-id');
        $this->session_mock->shouldReceive('setId')->with('former-session-id')->once();

        $this->auth_mock->shouldNotReceive('login');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB is down');

        $this->service->reloadSession('jti-1', '5');
    }
}
