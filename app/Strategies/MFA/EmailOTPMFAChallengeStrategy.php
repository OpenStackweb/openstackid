<?php namespace Strategies\MFA;

use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Models\OAuth2\Client;
use OAuth2\OAuth2Protocol;
use OAuth2\Services\ITokenService;

final class EmailOTPMFAChallengeStrategy extends AbstractMFAChallengeStrategy
{
    public function __construct(
        IUserRecoveryCodeRepository $recovery_code_repository,
        private readonly ITokenService $token_service,
        private readonly IOAuth2OTPRepository $otp_repository,
    ) {
        parent::__construct($recovery_code_repository);
    }

    public function issueChallenge(User $user, ?Client $client, bool $remember): array
    {
        $this->storePendingState($user->getId(), $remember);

        $otp = $this->token_service->createOTPFromPayload([
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend       => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail      => $user->getEmail(),
        ], $client);

        return [
            'otp_length'   => $otp->getLength(),
            'otp_lifetime' => $otp->getLifetime(),
        ];
    }

    public function verifyChallenge(User $user, string $code): void
    {
        $otp = $this->otp_repository->getByValueConnectionAndUserName(
            $code,
            OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            $user->getEmail()
        );

        if (is_null($otp)) {
            throw new AuthenticationException("Non existent single-use code.");
        }

        $otp->logRedeemAttempt();

        if (!$otp->isAlive()) {
            throw new AuthenticationException("Verification code is expired.");
        }

        if (!$otp->isValid()) {
            throw new AuthenticationException("Verification code is not valid.");
        }

        $otp->redeem();

        foreach ($this->otp_repository->getByUserNameNotRedeemed($user->getEmail()) as $otpToRevoke) {
            if ($otpToRevoke->getValue() !== $otp->getValue()) {
                $otpToRevoke->redeem();
            }
        }
    }

    public function resendChallenge(User $user, ?Client $client, bool $remember): array
    {
        return $this->issueChallenge($user, $client, $remember);
    }
}
