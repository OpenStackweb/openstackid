<?php namespace Strategies\MFA;

use Auth\Exceptions\AuthenticationException;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

abstract class AbstractMFAChallengeStrategy implements IMFAChallengeStrategy
{
    private const SESSION_TTL           = 300;
    private const KEY_USER_ID           = '2fa_pending_user_id';
    private const KEY_PENDING_AT        = '2fa_pending_at';
    private const KEY_REMEMBER          = '2fa_remember';
    private const KEY_RECOVERY_ATTEMPTS = '2fa_recovery_attempts';

    public function __construct(protected IUserRecoveryCodeRepository $recovery_code_repository) {}

    public function getPendingState(): ?array
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

        return [
            'user_id'    => $user_id,
            'pending_at' => $pending_at,
            'remember'   => Session::get(self::KEY_REMEMBER, false),
        ];
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
        foreach ($this->recovery_code_repository->getUnusedByUser($user) as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->getCodeHash())) {
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
}
