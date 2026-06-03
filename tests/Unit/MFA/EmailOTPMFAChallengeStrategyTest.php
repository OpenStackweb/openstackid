<?php namespace Tests\Unit\MFA;

use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
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

        $storedOtp = \Mockery::mock(OAuth2OTP::class);
        $storedOtp->shouldReceive('getValue')->andReturn($code);
        $storedOtp->shouldReceive('logRedeemAttempt')->once();
        $storedOtp->shouldReceive('isAlive')->andReturn(true);
        $storedOtp->shouldReceive('isValid')->andReturn(true);
        $storedOtp->shouldReceive('redeem')->once();

        $this->otpRepository
            ->shouldReceive('getByValueConnectionAndUserName')
            ->once()
            ->with($code, 'email', 'verify@example.com', null)
            ->andReturn($storedOtp);

        $otherOtp = \Mockery::mock(OAuth2OTP::class);
        $otherOtp->shouldReceive('getValue')->andReturn('654321');
        $otherOtp->shouldReceive('redeem')->once();

        $this->otpRepository
            ->shouldReceive('getByUserNameNotRedeemed')
            ->andReturn([$otherOtp]);

        // The redeemed code and the revoked sibling are both persisted with deferred flush.
        $this->otpRepository->shouldReceive('add')->with($storedOtp, false)->once();
        $this->otpRepository->shouldReceive('add')->with($otherOtp, false)->once();

        $this->strategy->verifyChallenge($user, $code);
        $this->addToAssertionCount(1);
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
