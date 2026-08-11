<?php namespace Strategies\MFA;

use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Models\OAuth2\Client;

abstract class AbstractMFAChallengeStrategy implements IMFAChallengeStrategy
{
    private const SESSION_TTL           = 300;
    private const KEY_USER_ID           = '2fa_pending_user_id';
    private const KEY_PENDING_AT        = '2fa_pending_at';
    private const KEY_REMEMBER          = '2fa_remember';
    private const KEY_RECOVERY_ATTEMPTS = '2fa_recovery_attempts';

    public function __construct(protected IUserRecoveryCodeRepository $recovery_code_repository) {}

    public function getPendingState(): ?MFAPendingState
    {
        $user_id    = Session::get(self::KEY_USER_ID);
        $pending_at = Session::get(self::KEY_PENDING_AT);

        if (is_null($user_id) || is_null($pending_at)) {
            return null;
        }

        if ((time() - $pending_at) > self::SESSION_TTL) {
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

    public function verifyRecoveryCode(User $user, string $code): void
    {
        // Recovery codes are hashed without the "-" separator; it is added only
        // for on-screen readability (XXXX-XXXX). Normalize here so a code typed
        // or pasted exactly as displayed still matches the stored hash.
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        foreach ($this->recovery_code_repository->getUnusedByUser($user) as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->getCodeHash())) {
                // Concurrency: acquire a PESSIMISTIC_WRITE row lock and re-hydrate
                // used_at before mutating. This closes the check->markUsed race
                // window: a second concurrent submitter blocks on the lock and, on
                // resume, sees the code already used instead of double-spending it.
                $this->recovery_code_repository->refreshExclusiveLock($recoveryCode);
                if ($recoveryCode->isUsed()) {
                    throw new AuthenticationException("Invalid recovery code.");
                }
                $recoveryCode->markUsed();
                return;
            }
        }
        throw new AuthenticationException("Invalid recovery code.");
    }

    protected function storePendingState(int $userId, bool $remember): void
    {
        Session::put(self::KEY_USER_ID,    $userId);
        Session::put(self::KEY_PENDING_AT, time());
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
