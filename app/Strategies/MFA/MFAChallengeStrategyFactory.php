<?php namespace Strategies\MFA;

final class MFAChallengeStrategyFactory
{
    public static function create(string $method): IMFAChallengeStrategy
    {
        return match($method) {
            'email_otp' => app()->make(EmailOTPMFAChallengeStrategy::class),
            default     => throw new \InvalidArgumentException("Unknown MFA method: {$method}"),
        };
    }
}
