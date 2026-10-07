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

use Auth\User;
use models\exceptions\ValidationException;

/**
 * Interface IRecoveryCodeService
 * @package App\Services\Auth
 */
interface IRecoveryCodeService
{
    /**
     * Invalidates every existing recovery code for the user and generates a fresh
     * batch. Plaintext codes are returned once and are never persisted/exposed again.
     *
     * A user with a password must confirm it. A user without one (social-only
     * accounts) is accepted instead while the session is inside the enrollment
     * window (isWithinEnrollmentWindow()).
     *
     * @param User $user
     * @param string|null $currentPassword
     * @return RecoveryCodesBatch codes formatted as XXXX-XXXX plus the batch identifier
     * @throws ValidationException if the password does not match, or a passwordless user is outside the window
     */
    public function regenerateRecoveryCodes(User $user, ?string $currentPassword): RecoveryCodesBatch;

    /**
     * Invalidates every existing recovery code for the user and generates a fresh
     * batch, without requiring password confirmation. Callers must already have
     * established the user's identity (enrollment endpoint: recent 2FA challenge).
     *
     * @param User $user
     * @return RecoveryCodesBatch codes formatted as XXXX-XXXX plus the batch identifier
     */
    public function generateRecoveryCodes(User $user): RecoveryCodesBatch;

    /**
     * Enrolls the user into the given 2FA method and generates the first batch
     * of recovery codes for them, without requiring password confirmation.
     * Intended for enrollment via an already-authenticated session.
     *
     * @param User $user
     * @param string $method
     * @return RecoveryCodesBatch codes formatted as XXXX-XXXX plus the batch identifier
     * @throws ValidationException if $method is not a valid/enabled 2FA method
     */
    public function enableTwoFactorAndGenerateCodes(User $user, string $method): RecoveryCodesBatch;

    /**
     * Confirms that the user saved the batch they were shown. Runs under the
     * users-row lock: the confirmation is bound to the exact batch.
     *
     * @param User $user
     * @param int $batchId identifier returned when the batch was generated
     * @return bool false when $batchId is not the user's current batch (nothing
     *              changes; the controller answers 412). True otherwise, also when
     *              the batch was already acknowledged (no second audit row).
     */
    public function acknowledgeRecoveryCodes(User $user, int $batchId): bool;

    /**
     * @param User $user
     * @return int|null smallest id of the user's recovery-code rows, null when none
     */
    public function getCurrentBatchId(User $user): ?int;

    /**
     * shouldRequire2FA() AND (no unused codes OR the batch is unacknowledged).
     *
     * @param User $user
     * @return bool
     */
    public function requiresRecoveryCodeEnrollment(User $user): bool;

    /**
     * Whether the session completed a real 2FA challenge for this user within
     * two_factor.recovery_enrollment_window seconds (MFAConstants::SESSION_KEY_COMPLETED_AT).
     *
     * @param User $user
     * @return bool
     */
    public function isWithinEnrollmentWindow(User $user): bool;

    /**
     * @param User $user
     * @return int count of unused recovery codes
     */
    public function countUnusedRecoveryCodes(User $user): int;

    /**
     * @param User $user
     * @return RecoveryCodesStatus remaining/total/low-threshold/acknowledged/enrollment-required standing
     */
    public function getStatus(User $user): RecoveryCodesStatus;

    /**
     * Disables 2FA for the user: clears two_factor_enabled and two_factor_enforced_at,
     * deletes every recovery code and revokes every trusted device, all in a single
     * transaction. A device_revoked audit event is recorded best-effort afterwards.
     *
     * $actor identifies who performs the operation:
     *  - null: a system/console operation (e.g. idp:reset-2fa), no password is required;
     *  - the same user: self-service, $currentPassword must match;
     *  - another user: an administrator acting on the account, no password is required.
     *    Authorizing the administrator is the caller's responsibility.
     *
     * The caller is responsible for emitting the settings_changed audit event, as only
     * it knows the context (reason, actor, operator).
     *
     * Group-enforced users are still required to complete 2FA at login afterwards,
     * as enforcement is derived from group membership (User::shouldRequire2FA()).
     *
     * @param User $user
     * @param string|null $currentPassword
     * @param User|null $actor
     * @return void
     * @throws ValidationException if the actor is the user and $currentPassword does not match
     */
    public function disableTwoFactor(User $user, ?string $currentPassword, ?User $actor): void;
}
