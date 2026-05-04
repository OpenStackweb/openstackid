<?php namespace Tests\Unit\MFA;

use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Session;
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

    public function testVerifyChallenge_withValidOtp_redeemsAndRevokesOthers(): void
    {
        $user = $this->buildUser(1, 'verify@example.com');
        $code = '123456';

        $otp = \Mockery::mock(OAuth2OTP::class);
        $otp->shouldReceive('logRedeemAttempt')->once();
        $otp->shouldReceive('isAlive')->andReturn(true);
        $otp->shouldReceive('isValid')->andReturn(true);
        $otp->shouldReceive('redeem')->once();
        $otp->shouldReceive('getValue')->andReturn($code);

        $otherOtp = \Mockery::mock(OAuth2OTP::class);
        $otherOtp->shouldReceive('getValue')->andReturn('654321');
        $otherOtp->shouldReceive('redeem')->once();

        $this->otpRepository
            ->shouldReceive('getByValueConnectionAndUserName')
            ->andReturn($otp);

        $this->otpRepository
            ->shouldReceive('getByUserNameNotRedeemed')
            ->andReturn([$otp, $otherOtp]);

        $this->strategy->verifyChallenge($user, $code);
        $this->addToAssertionCount(1);
    }

    public function testVerifyChallenge_withExpiredOtp_throwsException(): void
    {
        $user = $this->buildUser(2, 'expired@example.com');

        $otp = \Mockery::mock(OAuth2OTP::class);
        $otp->shouldReceive('logRedeemAttempt')->once();
        $otp->shouldReceive('isAlive')->andReturn(false);

        $this->otpRepository->shouldReceive('getByValueConnectionAndUserName')->andReturn($otp);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Verification code is expired.");
        $this->strategy->verifyChallenge($user, '000000');
    }

    public function testVerifyChallenge_withMaxAttemptsExceeded_throwsException(): void
    {
        $user = $this->buildUser(3, 'maxattempts@example.com');

        $otp = \Mockery::mock(OAuth2OTP::class);
        $otp->shouldReceive('logRedeemAttempt')->once();
        $otp->shouldReceive('isAlive')->andReturn(true);
        $otp->shouldReceive('isValid')->andReturn(false);

        $this->otpRepository->shouldReceive('getByValueConnectionAndUserName')->andReturn($otp);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Verification code is not valid.");
        $this->strategy->verifyChallenge($user, '111111');
    }

    public function testVerifyChallenge_withNonExistentOtp_throwsException(): void
    {
        $user = $this->buildUser(4, 'noexist@example.com');

        $this->otpRepository->shouldReceive('getByValueConnectionAndUserName')->andReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Non existent single-use code.");
        $this->strategy->verifyChallenge($user, 'BADCODE');
    }
}
