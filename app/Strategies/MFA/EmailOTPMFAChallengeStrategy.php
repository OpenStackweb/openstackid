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
            'otp_length'    => $otp->getLength(),
            'otp_lifetime'  => $otp->getLifetime(),
            // Same source isAlive()/getRemainingLifetime() use server-side, so a
            // UI countdown seeded from it can never drift from the actual expiry check.
            'otp_issued_at' => $otp->getCreatedAt()?->getTimestamp() ?? time(),
        ];
    }

    public function verifyChallenge(User $user, string $code, ?Client $client = null): void
    {
        // Look up the STORED single-use code so the submitted value is actually
        // validated against what was issued (a non-matching code resolves to null).
        // Scope the lookup to the issuing client so an MFA OTP is only matched
        // against the client it was issued for.
        $otp = $this->otp_repository->getByValueConnectionAndUserName(
            $code,
            OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            $user->getEmail(),
            $client
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

        // Concurrency: acquire a PESSIMISTIC_WRITE row lock and re-hydrate redemption
        // state before redeeming, mirroring AuthService::finalizeRedemption(). This
        // closes the validate->redeem race so two concurrent submissions of the same
        // valid code cannot both succeed. Runs inside the verifyMFAChallenge tx.
        if ($otp->getConnection() !== OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
            $this->otp_repository->refreshExclusiveLock($otp);
            if ($otp->isRedeemed()) {
                throw new AuthenticationException("Verification code is already redeemed.");
            }
        }

        $otp->redeem();

        // Revoke other pending OTPs for this user, scoped to the same client so we
        // never burn unrelated OTPs (e.g. passwordless-login codes for other clients).
        foreach ($this->otp_repository->getByUserNameNotRedeemed($user->getEmail(), $client) as $otpToRevoke) {
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
