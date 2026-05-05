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
use Auth\CustomAuthProvider;
use Auth\Exceptions\AuthenticationException;
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
 * Class AuthServiceValidateCredentialsTest
 * Verifies that AuthService::validateCredentials() validates the password
 * WITHOUT establishing a session, and that AuthService::loginUser() calls
 * Auth::login() for the 2FA completion step.
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class AuthServiceValidateCredentialsTest extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    private AuthService $service;

    private $mock_user_repository;

    // Facade aliases
    private $auth_mock;
    private $log_mock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock_user_repository = $this->createMock(IUserRepository::class);
        $mock_otp_repository = $this->createMock(IOAuth2OTPRepository::class);
        $mock_principal_service = $this->createMock(IPrincipalService::class);
        $mock_user_service = $this->createMock(IUserService::class);
        $mock_user_action_service = $this->createMock(IUserActionService::class);
        $mock_cache_service = $this->createMock(ICacheService::class);
        $mock_auth_user_service = $this->createMock(IAuthUserService::class);
        $mock_security_context_service = $this->createMock(ISecurityContextService::class);
        $mock_tx_service = $this->createMock(ITransactionService::class);

        $this->auth_mock = Mockery::mock('alias:Illuminate\Support\Facades\Auth');
        $this->log_mock = Mockery::mock('alias:Illuminate\Support\Facades\Log');

        $this->log_mock->shouldReceive('debug')->zeroOrMoreTimes();
        $this->log_mock->shouldReceive('warning')->zeroOrMoreTimes();

        $this->service = new AuthService(
            $this->mock_user_repository,
            $mock_otp_repository,
            $mock_principal_service,
            $mock_user_service,
            $mock_user_action_service,
            $mock_cache_service,
            $mock_auth_user_service,
            $mock_security_context_service,
            $mock_tx_service
        );
    }

    /**
     * Valid credentials return the User WITHOUT establishing a session.
     * Auth::login() and Auth::attempt() must NEVER be called.
     */
    public function testValidCredentials_returnsUser_withoutEstablishingSession(): void
    {
        $username = 'jane.doe';
        $password = 'Str0ng!Pass';

        $resolved_user = Mockery::mock('Auth\User');

        $provider_mock = Mockery::mock(CustomAuthProvider::class);
        $provider_mock->shouldReceive('retrieveByCredentials')
            ->once()
            ->with(['username' => $username, 'password' => $password])
            ->andReturn($resolved_user);

        $this->auth_mock->shouldReceive('getProvider')->once()->andReturn($provider_mock);
        $this->auth_mock->shouldNotReceive('login');
        $this->auth_mock->shouldNotReceive('attempt');

        $returned = $this->service->validateCredentials($username, $password);

        $this->assertSame($resolved_user, $returned);
    }

    /**
     * Invalid credentials (provider returns null) throw AuthenticationException
     * and do NOT establish a session.
     */
    public function testInvalidCredentials_throwsAuthenticationException(): void
    {
        $username = 'jane.doe';
        $password = 'wrong';

        $provider_mock = Mockery::mock(CustomAuthProvider::class);
        $provider_mock->shouldReceive('retrieveByCredentials')
            ->once()
            ->with(['username' => $username, 'password' => $password])
            ->andReturn(null);

        $this->auth_mock->shouldReceive('getProvider')->once()->andReturn($provider_mock);
        $this->auth_mock->shouldNotReceive('login');
        $this->auth_mock->shouldNotReceive('attempt');

        $this->expectException(AuthenticationException::class);

        $this->service->validateCredentials($username, $password);
    }

    /**
     * loginUser(user, true) delegates to Auth::login with the remember flag set.
     */
    public function testLoginUser_callsAuthLogin_withRememberTrue(): void
    {
        $user = Mockery::mock('Auth\User');
        $user->shouldReceive('canLogin')->andReturn(true);

        $this->auth_mock
            ->shouldReceive('login')
            ->once()
            ->with($user, true);

        $this->service->loginUser($user, true);
    }

    /**
     * loginUser(user, false) delegates to Auth::login with remember disabled.
     */
    public function testLoginUser_callsAuthLogin_withRememberFalse(): void
    {
        $user = Mockery::mock('Auth\User');
        $user->shouldReceive('canLogin')->andReturn(true);

        $this->auth_mock
            ->shouldReceive('login')
            ->once()
            ->with($user, false);

        $this->service->loginUser($user, false);
    }

    /**
     * loginUser(user, [true|false]) and isActive or canLogin false throws an Exception.
     */
    public function testLoginUser_throwsException_whenIsNotActive(): void
    {
        $user = Mockery::mock('Auth\User');
        $user->shouldReceive('canLogin')->andReturn(false);

        $this->auth_mock->shouldNotReceive('login');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/User is not active or cannot login\./');

        $this->service->loginUser($user, true);
    }

}
