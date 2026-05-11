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
use App\Services\Auth\IUserService as IAuthUserService;
use Auth\AuthService;
use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OpenId\Services\IUserService;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Services\IUserActionService;
use Utils\Db\ITransactionService;
use Utils\Services\ICacheService;

/**
 * Verifies the MFA-binding contract of AuthService::verifyOTPChallenge:
 * the OTP must resolve to the same user as the in-session user, otherwise
 * the call rejects WITHOUT consuming the OTP (no refresh-and-lock, no redeem,
 * no sibling revocation).
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
final class VerifyOTPChallengeTest extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    private AuthService $service;

    /** @var IOAuth2OTPRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $mock_otp_repository;

    /** @var IUserRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $mock_user_repository;

    private $log_mock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock_user_repository       = $this->createMock(IUserRepository::class);
        $this->mock_otp_repository        = $this->createMock(IOAuth2OTPRepository::class);
        $mock_principal_service           = $this->createMock(IPrincipalService::class);
        $mock_user_service                = $this->createMock(IUserService::class);
        $mock_user_action_service         = $this->createMock(IUserActionService::class);
        $mock_cache_service               = $this->createMock(ICacheService::class);
        $mock_auth_user_service           = $this->createMock(IAuthUserService::class);
        $mock_security_context_service    = $this->createMock(ISecurityContextService::class);
        $mock_tx_service                  = $this->createMock(ITransactionService::class);

        // Execute each transaction closure in-process; this is the unit under test,
        // not Doctrine.
        $mock_tx_service
            ->method('transaction')
            ->willReturnCallback(fn(\Closure $cb) => $cb());

        $this->log_mock = Mockery::mock('alias:Illuminate\Support\Facades\Log');
        $this->log_mock->shouldReceive('debug')->zeroOrMoreTimes();
        $this->log_mock->shouldReceive('warning')->zeroOrMoreTimes();

        $this->service = new AuthService(
            $this->mock_user_repository,
            $this->mock_otp_repository,
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
     * Cross-account: an OTP minted for user B is submitted by a session
     * authenticated as user A. The call MUST throw and MUST NOT consume the OTP.
     *
     * "Not consumed" is observed by asserting that finalizeRedemption never
     * runs: neither the row lock (refreshExclusiveLock) nor the sibling-revoke
     * lookup (getByUserNameNotRedeemed) is touched.
     */
    public function testRejectsAndPreservesOTPWhenSessionUserDiffersFromOTPUser(): void
    {
        $otp = $this->buildValidOTPMock('user-b@example.com');

        $this->mock_otp_repository
            ->expects($this->once())
            ->method('getByValueConnectionAndUserName')
            ->with('123456', 'email', 'user-b@example.com', null)
            ->willReturn($otp);

        // OTP-resolved user (the rightful owner of the code)
        $otp_owner = Mockery::mock(User::class);
        $otp_owner->shouldReceive('getId')->andReturn(200);
        $otp_owner->shouldReceive('canLogin')->andReturn(true);

        $this->mock_user_repository
            ->expects($this->once())
            ->method('getByEmailOrName')
            ->with('user-b@example.com')
            ->willReturn($otp_owner);

        // Session-authenticated user — different id; this is the MFA bypass attempt.
        $session_user = Mockery::mock(User::class);
        $session_user->shouldReceive('getId')->andReturn(100);

        // ⚡ The contract: finalizeRedemption must NOT be reached. Both of its
        // observable repository calls are forbidden.
        $this->mock_otp_repository
            ->expects($this->never())
            ->method('refreshExclusiveLock');

        $this->mock_otp_repository
            ->expects($this->never())
            ->method('getByUserNameNotRedeemed');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Single-use code does not belong to the authenticated user.');

        $this->service->verifyOTPChallenge($this->buildClaim('user-b@example.com'), $session_user);
    }

    /**
     * Same-account: OTP belongs to the session user. The call must reach
     * finalizeRedemption and consume the OTP (refreshExclusiveLock acquires
     * the row lock; redeem() runs; sibling revocation queries the repo).
     */
    public function testSucceedsAndConsumesOTPWhenSessionUserMatchesOTPUser(): void
    {
        $otp = $this->buildValidOTPMock('user-a@example.com');

        // finalizeRedemption mutations expected
        $otp->shouldReceive('setAuthTime')->once();
        $otp->shouldReceive('setUserId')->once()->with(100);
        $otp->shouldReceive('redeem')->once();
        $otp->shouldReceive('getId')->andReturn(7);

        $this->mock_otp_repository
            ->expects($this->once())
            ->method('getByValueConnectionAndUserName')
            ->willReturn($otp);

        $session_user = Mockery::mock(User::class);
        $session_user->shouldReceive('getId')->andReturn(100);
        $session_user->shouldReceive('canLogin')->andReturn(true);

        $this->mock_user_repository
            ->expects($this->once())
            ->method('getByEmailOrName')
            ->with('user-a@example.com')
            ->willReturn($session_user);

        // finalizeRedemption MUST run: lock acquired, sibling sweep performed.
        $this->mock_otp_repository
            ->expects($this->once())
            ->method('refreshExclusiveLock')
            ->with($otp);

        $this->mock_otp_repository
            ->expects($this->once())
            ->method('getByUserNameNotRedeemed')
            ->with('user-a@example.com', null)
            ->willReturn([]);

        $result = $this->service->verifyOTPChallenge($this->buildClaim('user-a@example.com'), $session_user);

        $this->assertSame($otp, $result);
    }

    private function buildClaim(string $user_name): OAuth2OTP
    {
        $claim = Mockery::mock(OAuth2OTP::class);
        $claim->shouldReceive('getValue')->andReturn('123456');
        $claim->shouldReceive('getUserName')->andReturn($user_name);
        $claim->shouldReceive('getConnection')->andReturn('email');
        $claim->shouldReceive('getScope')->andReturn(null);
        return $claim;
    }

    /**
     * A resolved OTP that passes findAndValidateOTP (alive, valid, value matches,
     * scope OK, audience OK) and is not already redeemed.
     */
    private function buildValidOTPMock(string $user_name): Mockery\MockInterface
    {
        $otp = Mockery::mock(OAuth2OTP::class);
        $otp->shouldReceive('getValue')->andReturn('123456');
        $otp->shouldReceive('getUserName')->andReturn($user_name);
        $otp->shouldReceive('getConnection')->andReturn('email');
        $otp->shouldReceive('logRedeemAttempt')->zeroOrMoreTimes();
        $otp->shouldReceive('isAlive')->andReturn(true);
        $otp->shouldReceive('isValid')->andReturn(true);
        $otp->shouldReceive('allowScope')->andReturn(true);
        $otp->shouldReceive('hasClient')->andReturn(false);
        $otp->shouldReceive('isRedeemed')->andReturn(false);
        return $otp;
    }
}
