<?php
namespace App\Services\Auth;
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

use App\libs\Auth\Models\TwoFactorAuditLog;
use App\Mail\TwoFactorEnabledByAdminMail;
use App\libs\Auth\Models\UserRecoveryCode;
use Auth\MFAConstants;
use Auth\Repositories\IUserRecoveryCodeRepository;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Laminas\Math\Rand;
use models\exceptions\ValidationException;
use Utils\Db\ITransactionService;
use Utils\IPHelper;

/**
 * Class RecoveryCodeService
 * @package App\Services\Auth
 */
final class RecoveryCodeService implements IRecoveryCodeService
{
    private const CODE_CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const AckMismatch = 'mismatch';
    private const AckAlready  = 'already';
    private const AckDone     = 'done';

    public function __construct(
        private readonly IUserRecoveryCodeRepository $repository,
        private readonly IUserRepository $user_repository,
        private readonly IUserTrustedDeviceRepository $trusted_device_repository,
        private readonly ITransactionService $tx_service,
        private readonly ITwoFactorAuditService $audit_service,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function regenerateRecoveryCodes(User $user, ?string $currentPassword): RecoveryCodesBatch
    {
        if ($user->hasPasswordSet()) {
            if (!$user->checkPassword(trim((string)$currentPassword))) {
                throw new ValidationException('current_password is not correct.');
            }
        } elseif (!$this->isWithinEnrollmentWindow($user)) {
            // Social-only account: no password to confirm. Proof of identity is a
            // real 2FA challenge completed moments ago in this session.
            throw new ValidationException(
                'Sign out and sign in again, then regenerate your recovery codes right after the sign-in.'
            );
        }

        return $this->generateRecoveryCodes($user);
    }

    /**
     * @inheritDoc
     */
    public function generateRecoveryCodes(User $user): RecoveryCodesBatch
    {
        $batch = $this->tx_service->transaction(function () use ($user) {
            $this->repository->lockUserRow($user);
            return $this->regenerateCodesForUser($user);
        });

        $this->logCodesGenerated($user, $batch);

        return $batch;
    }

    /**
     * @inheritDoc
     */
    public function enableTwoFactorAndGenerateCodes(User $user, string $method): RecoveryCodesBatch
    {
        // Everything must live in a single transaction() call: it opens/commits
        // its own connection-level transaction and closes the entity manager on
        // failure (see DoctrineTransactionService::transaction()), so nesting a
        // second call inside it (e.g. by calling generateRecoveryCodes() here)
        // would let an inner failure tear down the EM out from under this
        // still-running outer transaction.
        $batch = $this->tx_service->transaction(function () use ($user, $method) {
            // The lock re-hydrates $user from the database, so it must come
            // before enable2FA() mutates the entity.
            $this->repository->lockUserRow($user);
            $user->enable2FA($method);
            $this->user_repository->add($user, false);

            return $this->regenerateCodesForUser($user);
        });

        // Best-effort: 2FA is already enabled and the codes are already
        // committed, so an audit-logging failure must not 500 this response - a
        // client retry on a 500 would regenerate and invalidate the codes it
        // just received.
        $this->logCodesGenerated($user, $batch);
        try {
            $this->audit_service->log(
                $user,
                TwoFactorAuditLog::EventEnrollmentChanged,
                $method,
                IPHelper::getUserIp()
            );
        } catch (\Throwable $ex) {
            Log::warning($ex);
        }

        return $batch;
    }

    /**
     * @inheritDoc
     */
    public function acknowledgeRecoveryCodes(User $user, int $batchId): bool
    {
        // The batch mismatch is reported through the return value, not by
        // throwing inside the callback: a throw makes the transaction service
        // close the EntityManager, which nothing after it could use.
        $outcome = $this->tx_service->transaction(function () use ($user, $batchId) {
            $this->repository->lockUserRow($user);

            $current = $this->repository->getCurrentBatchId($user);
            if (is_null($current) || $current !== $batchId) {
                return self::AckMismatch;
            }

            if (!is_null($user->getRecoveryCodesAcknowledgedAt())) {
                return self::AckAlready;
            }

            $user->setRecoveryCodesAcknowledgedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            return self::AckDone;
        });

        if ($outcome === self::AckMismatch) {
            return false;
        }

        if ($outcome === self::AckDone) {
            // Best-effort: the acknowledgment is already committed.
            try {
                $this->audit_service->log(
                    $user,
                    TwoFactorAuditLog::EventRecoveryCodesAcknowledged,
                    TwoFactorAuditLog::MethodRecovery,
                    IPHelper::getUserIp(),
                    ['batch_id' => $batchId]
                );
            } catch (\Throwable $ex) {
                Log::warning($ex);
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentBatchId(User $user): ?int
    {
        return $this->repository->getCurrentBatchId($user);
    }

    /**
     * @inheritDoc
     */
    public function requiresRecoveryCodeEnrollment(User $user): bool
    {
        return $this->enrollmentRequired($user, $this->countUnusedRecoveryCodes($user));
    }

    /**
     * @inheritDoc
     */
    public function isWithinEnrollmentWindow(User $user): bool
    {
        $marker = Session::get(MFAConstants::SESSION_KEY_COMPLETED_AT);
        if (!is_array($marker) || !isset($marker['user_id'], $marker['at'])) {
            return false;
        }

        if ((int)$marker['user_id'] !== $user->getId()) {
            return false;
        }

        $age = time() - (int)$marker['at'];
        return $age >= 0 && $age <= (int)config('two_factor.recovery_enrollment_window', 600);
    }

    private function enrollmentRequired(User $user, int $unusedCount): bool
    {
        return $user->shouldRequire2FA()
            && ($unusedCount === 0 || is_null($user->getRecoveryCodesAcknowledgedAt()));
    }

    /**
     * Audit row of a generation. Best-effort: the codes are already committed
     * and about to be shown to the user, so an audit-logging failure must not
     * 500 this response - a client retry on a 500 would regenerate and
     * invalidate the codes it just received. The batch identifier comes from
     * the generating transaction, never from a read after the commit.
     */
    private function logCodesGenerated(User $user, RecoveryCodesBatch $batch): void
    {
        try {
            $this->audit_service->log(
                $user,
                TwoFactorAuditLog::EventRecoveryCodesGenerated,
                TwoFactorAuditLog::MethodRecovery,
                IPHelper::getUserIp(),
                ['batch_id' => $batch->getBatchId()]
            );
        } catch (\Throwable $ex) {
            Log::warning($ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function disableTwoFactor(User $user, ?string $currentPassword, ?User $actor): void
    {
        // Group enforcement does not depend on the stored flag: disabling would only
        // delete the recovery codes and trusted devices of a user who is still
        // challenged at login. The console (null actor) is the manual recovery path
        // (idp:reset-2fa) and stays allowed.
        if (!is_null($actor) && $user->isTwoFactorEnforcedByGroup()) {
            throw new ValidationException('Two-factor authentication is enforced by group membership and cannot be disabled.');
        }

        $is_self = !is_null($actor) && $actor->getId() === $user->getId();

        if ($is_self) {
            if ($user->hasPasswordSet()) {
                if (!$user->checkPassword(trim((string)$currentPassword))) {
                    throw new ValidationException('current_password is not correct.');
                }
            } elseif (!$this->isWithinEnrollmentWindow($user)) {
                // Social-only account: no password to confirm. Proof of identity is a
                // real 2FA challenge completed moments ago in this session.
                throw new ValidationException(
                    'Sign out and sign in again, then turn off two-factor authentication right after the sign-in.'
                );
            }
        }

        // a single transaction() call: nesting another one (e.g. through the audit
        // service) inside it would let an inner failure close the entity manager
        // under this still-running outer transaction
        $this->tx_service->transaction(function () use ($user) {
            // serializes with a concurrent regenerate: it also deletes and inserts
            // codes. The lock re-hydrates $user, so it must precede disable2FA().
            $this->repository->lockUserRow($user);
            $user->disable2FA();
            $user->setRecoveryCodesAcknowledgedAt(null);
            $this->user_repository->add($user, false);
            $this->repository->deleteAllForUser($user);
            $this->trusted_device_repository->revokeAllForUser($user);
        });

        // Best-effort: 2FA is already disabled and the codes and devices are gone,
        // an audit-logging failure must not make the caller believe it did not happen.
        try {
            $this->audit_service->log(
                $user,
                TwoFactorAuditLog::EventEnrollmentChanged,
                $user->getTwoFactorMethod(),
                IPHelper::getUserIp(),
                [
                    'enabled'  => false,
                    'actor_id' => $actor?->getId(),
                    'reason'   => $is_self ? 'self' : (is_null($actor) ? 'console' : 'admin'),
                ]
            );
        } catch (\Throwable $ex) {
            Log::warning($ex);
        }
    }

    /**
     * @inheritDoc
     */
    public function enableTwoFactorForUser(User $user, string $method, User $actor): void
    {
        $this->tx_service->transaction(function () use ($user, $method) {
            // the lock re-hydrates $user, so it must precede enable2FA()
            $this->repository->lockUserRow($user);
            $user->enable2FA($method);
            $this->user_repository->add($user, false);
        });

        try {
            $this->audit_service->log(
                $user,
                TwoFactorAuditLog::EventEnrollmentChanged,
                $method,
                IPHelper::getUserIp(),
                [
                    'enabled'  => true,
                    'actor_id' => $actor->getId(),
                    'reason'   => 'admin',
                ]
            );
        } catch (\Throwable $ex) {
            Log::warning($ex);
        }

        try {
            Mail::queue(new TwoFactorEnabledByAdminMail($user));
        } catch (\Throwable $ex) {
            Log::warning($ex);
        }
    }

    /**
     * Invalidates every existing recovery code for the user and generates a
     * fresh batch, within the caller's already-open transaction. The caller
     * must already hold the users-row lock (IUserRecoveryCodeRepository::lockUserRow()).
     *
     * Also clears recovery_codes_acknowledged_at: the new batch counts as usable
     * only after the user confirms it. Covers every generation path.
     */
    private function regenerateCodesForUser(User $user): RecoveryCodesBatch
    {
        $count = (int)config('auth.recovery_codes.count', 10);
        $length = (int)config('auth.recovery_codes.length', 8);

        $plaintext_codes = [];

        $this->repository->deleteAllForUser($user);

        for ($i = 0; $i < $count; $i++) {
            $plain = Rand::getString($length, self::CODE_CHARSET, true);
            $plaintext_codes[] = $plain;

            $code = new UserRecoveryCode();
            $code->setUser($user);
            $code->setCodeHash(Hash::make($plain));
            $this->repository->add($code, false);
        }

        $user->setRecoveryCodesAcknowledgedAt(null);

        // Read inside the locked transaction (getCurrentBatchId() flushes first).
        $batchId = $this->repository->getCurrentBatchId($user);
        if (is_null($batchId)) {
            throw new \RuntimeException('Recovery codes were not persisted.');
        }

        return new RecoveryCodesBatch(
            array_map(static fn(string $code) => implode('-', str_split($code, 4)), $plaintext_codes),
            $batchId
        );
    }

    /**
     * @inheritDoc
     */
    public function countUnusedRecoveryCodes(User $user): int
    {
        return count($this->repository->getUnusedByUser($user));
    }

    /**
     * @inheritDoc
     */
    public function getStatus(User $user): RecoveryCodesStatus
    {
        $remaining = $this->countUnusedRecoveryCodes($user);

        return new RecoveryCodesStatus(
            $remaining,
            (int) config('auth.recovery_codes.count', 10),
            (int) config('auth.recovery_codes.low_threshold', 3),
            !is_null($user->getRecoveryCodesAcknowledgedAt()),
            $this->enrollmentRequired($user, $remaining)
        );
    }
}
