<?php namespace Strategies\MFA;

use Auth\Exceptions\AuthenticationException;
use Auth\MFAConstants;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Models\OAuth2\Client;

abstract class AbstractMFAChallengeStrategy implements IMFAChallengeStrategy
{
    private const KEY_USER_ID           = MFAConstants::SESSION_KEY_PENDING_USER_ID;
    private const KEY_PENDING_AT        = MFAConstants::SESSION_KEY_PENDING_AT;
    private const KEY_REMEMBER          = MFAConstants::SESSION_KEY_REMEMBER;
    private const KEY_RECOVERY_ATTEMPTS = MFAConstants::SESSION_KEY_RECOVERY_ATTEMPTS;

    /**
     * Fallback for two_factor.otp_lifetime (missing or non-positive) and the
     * default of two_factor.session_ttl, so the pending session never
     * outlives the code (SDS idp-mfa.md §4.14).
     */
    protected const DEFAULT_OTP_LIFETIME = 600;

    public function __construct(protected IUserRecoveryCodeRepository $recovery_code_repository) {}

    public function getPendingState(): ?MFAPendingState
    {
        $user_id    = Session::get(self::KEY_USER_ID);
        $pending_at = Session::get(self::KEY_PENDING_AT);

        if (is_null($user_id) || is_null($pending_at)) {
            return null;
        }

        if ((time() - $pending_at) > self::resolvePendingSessionTtl()) {
            $this->clearPendingState();
            return null;
        }

        return new MFAPendingState(
            (int) $user_id,
            (int) $pending_at,
            (bool) Session::get(self::KEY_REMEMBER, false)
        );
    }

    public function clearPendingState(): void
    {
        Session::remove(self::KEY_USER_ID);
        Session::remove(self::KEY_PENDING_AT);
        Session::remove(self::KEY_REMEMBER);
        Session::remove(self::KEY_RECOVERY_ATTEMPTS);
    }

    /**
     * Lifetime (seconds) of every MFA challenge code. A non-positive value is a
     * misconfiguration (a second factor must always expire), so it falls back
     * to the default instead of issuing a never-expiring code.
     *
     * @return int
     */
    public static function resolveOtpLifetime(): int
    {
        $lifetime = intval(Config::get('two_factor.otp_lifetime', self::DEFAULT_OTP_LIFETIME));

        if ($lifetime <= 0) {
            Log::warning(sprintf(
                "AbstractMFAChallengeStrategy::resolveOtpLifetime two_factor.otp_lifetime is %d, falling back to %d seconds",
                $lifetime,
                self::DEFAULT_OTP_LIFETIME
            ));
            $lifetime = self::DEFAULT_OTP_LIFETIME;
        }

        return $lifetime;
    }

    /**
     * Seconds a pending challenge stays valid in the session: two_factor.session_ttl,
     * raised to the effective code lifetime so a configuration that lengthens the
     * code (or lowers the session TTL) can never expire the challenge while the
     * code, and its UI countdown, are still valid.
     *
     * @return int
     */
    public static function resolvePendingSessionTtl(): int
    {
        return max(
            intval(Config::get('two_factor.session_ttl', self::DEFAULT_OTP_LIFETIME)),
            self::resolveOtpLifetime()
        );
    }

    public function verifyRecoveryCode(User $user, string $code): void
    {
        // Recovery codes are hashed without the "-" separator; it is added only
        // for on-screen readability (XXXX-XXXX). Normalize here so a code typed
        // or pasted exactly as displayed still matches the stored hash.
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        // Lock order shared by every path that writes or deletes the user's
        // codes: the users row first, then user_recovery_codes (SDS idp-mfa.md
        // §3.5). Taken before reading the codes so a concurrent regeneration
        // or reset is fully applied or not started when we look at them.
        $this->recovery_code_repository->lockUserRow($user);

        foreach ($this->recovery_code_repository->getUnusedByUser($user) as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->getCodeHash())) {
                // Concurrency: acquire a PESSIMISTIC_WRITE row lock and re-hydrate
                // used_at before mutating. This closes the check->markUsed race
                // window: a second concurrent submitter blocks on the lock and, on
                // resume, sees the code already used instead of double-spending it.
                // A row that is gone under the lock was deleted by a concurrent
                // regeneration or reset after it was read: the code is no longer valid.
                if (!$this->recovery_code_repository->refreshExclusiveLock($recoveryCode)
                    || $recoveryCode->isUsed()) {
                    throw new AuthenticationException("Invalid recovery code.");
                }
                $recoveryCode->markUsed();
                return;
            }
        }
        throw new AuthenticationException("Invalid recovery code.");
    }

    /**
     * @param int $userId
     * @param bool $remember
     * @param int|null $pendingAt epoch seconds the challenge was issued at; pass the
     *                            code's own creation time so the pending session is
     *                            measured on the same clock as the code's expiry and
     *                            a slow issuance cannot leave the code valid for a
     *                            few seconds after the session expired. Defaults to now.
     */
    protected function storePendingState(int $userId, bool $remember, ?int $pendingAt = null): void
    {
        Session::put(self::KEY_USER_ID,    $userId);
        Session::put(self::KEY_PENDING_AT, $pendingAt ?? time());
        Session::put(self::KEY_REMEMBER,   $remember);
    }

    public function verifyChallenge(User $user, string $code, ?Client $client = null): void
    {
    }

    public function issueChallenge(User $user, ?Client $client, bool $remember): array
    {
        return [];
    }
}
