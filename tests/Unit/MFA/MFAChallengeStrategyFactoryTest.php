<?php namespace Tests\Unit\MFA;

use Strategies\MFA\EmailOTPMFAChallengeStrategy;
use Strategies\MFA\MFAChallengeStrategyFactory;
use Tests\TestCase;

class MFAChallengeStrategyFactoryTest extends TestCase
{
    public function testCreate_withEmailOtp_returnsEmailOTPMFAChallengeStrategy(): void
    {
        $strategy = MFAChallengeStrategyFactory::create('email_otp');

        $this->assertInstanceOf(EmailOTPMFAChallengeStrategy::class, $strategy);
    }

    public function testCreate_withUnknownMethod_throwsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown MFA method: sms_otp");

        MFAChallengeStrategyFactory::create('sms_otp');
    }
}
