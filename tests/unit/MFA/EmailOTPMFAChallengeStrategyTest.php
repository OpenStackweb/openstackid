<?php namespace Tests\unit\MFA;

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
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Session;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Services\ITokenService;
use Strategies\MFA\EmailOTPMFAChallengeStrategy;
use Tests\TestCase;

class EmailOTPMFAChallengeStrategyTest extends TestCase
{
    private EmailOTPMFAChallengeStrategy $strategy;
    private $tokenService;
    private $otpRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService  = \Mockery::mock(ITokenService::class);
        $this->otpRepository = \Mockery::mock(IOAuth2OTPRepository::class);
        $recoveryRepo        = \Mockery::mock(IUserRecoveryCodeRepository::class);

        $this->strategy = new EmailOTPMFAChallengeStrategy(
            $recoveryRepo,
            $this->tokenService,
            $this->otpRepository,
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    private function buildUser(int $id, string $email): User
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getEmail')->andReturn($email);
        return $user;
    }

    // ---------- issueChallenge ----------

    public function testIssueChallenge_storesPendingStateAndReturnsOtpInfo(): void
    {
        $user = $this->buildUser(42, 'user@example.com');

        $otp = \Mockery::mock(OAuth2OTP::class);
        $otp->shouldReceive('getLength')->andReturn(6);
        $otp->shouldReceive('getLifetime')->andReturn(120);

        $this->tokenService
            ->shouldReceive('createOTPFromPayload')
            ->once()
            ->withArgs(function (array $payload, $client) {
                return $payload['connection'] === 'email'
                    && $payload['send'] === 'code'
                    && $payload['email'] === 'user@example.com'
                    && is_null($client);
            })
            ->andReturn($otp);

        $result = $this->strategy->issueChallenge($user, null, true);

        $this->assertSame(['otp_length' => 6, 'otp_lifetime' => 120], $result);
        $this->assertSame(42, Session::get('2fa_pending_user_id'));
        $this->assertTrue(Session::get('2fa_remember'));
    }

    // ---------- resendChallenge ----------

    public function testResendChallenge_delegatesToIssueChallenge(): void
    {
        $user = $this->buildUser(7, 'resend@example.com');

        $otp = \Mockery::mock(OAuth2OTP::class);
        $otp->shouldReceive('getLength')->andReturn(6);
        $otp->shouldReceive('getLifetime')->andReturn(120);

        $this->tokenService
            ->shouldReceive('createOTPFromPayload')
            ->once()
            ->andReturn($otp);

        $result = $this->strategy->resendChallenge($user, null, false);

        $this->assertSame(['otp_length' => 6, 'otp_lifetime' => 120], $result);
        $this->assertSame(7, Session::get('2fa_pending_user_id'));
    }

    // ---------- verifyChallenge ----------

    public function testVerifyChallenge_withValidOtp_redeemsAndRevokesOthers_scopedToClient(): void
    {
        $user   = $this->buildUser(1, 'verify@example.com');
        $code   = '123456';
        $client = \Mockery::mock(Client::class);

        $storedOtp = \Mockery::mock(OAuth2OTP::class);
        $storedOtp->shouldReceive('getValue')->andReturn($code);
        $storedOtp->shouldReceive('logRedeemAttempt')->once();
        $storedOtp->shouldReceive('isAlive')->andReturn(true);
        $storedOtp->shouldReceive('isValid')->andReturn(true);
        $storedOtp->shouldReceive('getConnection')->andReturn('email');
        $storedOtp->shouldReceive('isRedeemed')->andReturn(false);
        $storedOtp->shouldReceive('redeem')->once();

        // Lookup MUST be scoped to the issuing client (regression guard for r3357348448).
        $this->otpRepository
            ->shouldReceive('getByValueConnectionAndUserName')
            ->once()
            ->with($code, 'email', 'verify@example.com', $client)
            ->andReturn($storedOtp);

        // A pessimistic row lock is taken before redeeming (regression guard for 3357348444).
        $this->otpRepository->shouldReceive('refreshExclusiveLock')->with($storedOtp)->once();

        $otherOtp = \Mockery::mock(OAuth2OTP::class);
        $otherOtp->shouldReceive('getValue')->andReturn('654321');
        $otherOtp->shouldReceive('redeem')->once();

        // Sibling revoke MUST be scoped to the SAME client so unrelated OTPs survive.
        $this->otpRepository
            ->shouldReceive('getByUserNameNotRedeemed')
            ->once()
            ->with('verify@example.com', $client)
            ->andReturn([$otherOtp]);

        // The redeemed code and the revoked sibling are both persisted with deferred flush.

        $this->strategy->verifyChallenge($user, $code, $client);
        $this->addToAssertionCount(1);
    }

    public function testVerifyChallenge_acquiresLock_andRejectsAlreadyRedeemed(): void
    {
        $user = $this->buildUser(1, 'verify@example.com');
        $code = '123456';

        $storedOtp = \Mockery::mock(OAuth2OTP::class);
        $storedOtp->shouldReceive('getValue')->andReturn($code);
        $storedOtp->shouldReceive('logRedeemAttempt')->once();
        $storedOtp->shouldReceive('isAlive')->andReturn(true);
        $storedOtp->shouldReceive('isValid')->andReturn(true);
        $storedOtp->shouldReceive('getConnection')->andReturn('email');
        // Concurrent winner already redeemed the row under the lock.
        $storedOtp->shouldReceive('isRedeemed')->andReturn(true);
        // Must NOT redeem again nor sweep siblings.
        $storedOtp->shouldReceive('redeem')->never();

        $this->otpRepository
            ->shouldReceive('getByValueConnectionAndUserName')
            ->once()
            ->andReturn($storedOtp);

        $this->otpRepository->shouldReceive('refreshExclusiveLock')->with($storedOtp)->once();
        $this->otpRepository->shouldReceive('getByUserNameNotRedeemed')->never();
        $this->otpRepository->shouldReceive('add')->never();

        $this->expectException(\Auth\Exceptions\AuthenticationException::class);
        $this->strategy->verifyChallenge($user, $code);
    }

    public function testVerifyChallenge_withNonMatchingCode_throws(): void
    {
        $user = $this->buildUser(1, 'verify@example.com');

        $this->otpRepository
            ->shouldReceive('getByValueConnectionAndUserName')
            ->once()
            ->andReturn(null);

        $this->expectException(\Auth\Exceptions\AuthenticationException::class);
        $this->strategy->verifyChallenge($user, 'wrong-code');
    }
}
