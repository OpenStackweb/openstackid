<?php namespace Auth\Repositories;
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
use App\libs\Auth\Models\UserRecoveryCode;
use Auth\User;
use models\utils\IBaseRepository;

interface IUserRecoveryCodeRepository extends IBaseRepository
{
    /**
     * @return UserRecoveryCode[] all unused codes for a user
     */
    public function getUnusedByUser(User $user): array;

    /**
     * Acquires a PESSIMISTIC_WRITE row lock on the given recovery code and
     * re-hydrates its used_at state in the same round-trip. Required before
     * redeeming a recovery code to close the check->markUsed double-spend race.
     * Must run inside a transaction.
     *
     * @return bool false when the row no longer exists (deleted by a concurrent
     *              regeneration or 2FA reset after the code was read); the
     *              entity is then stale and the code must not be redeemed
     */
    public function refreshExclusiveLock(UserRecoveryCode $code): bool;

    /**
     * Delete every recovery code for a user (used when regenerating).
     */
    public function deleteAllForUser(User $user): int;

    /**
     * Takes a PESSIMISTIC_WRITE lock on the user's `users` row and re-hydrates
     * the entity from it, so state read afterwards (recovery_codes_acknowledged_at)
     * is current. Every path that writes or deletes the user's recovery codes
     * calls this first, inside its transaction, and only then touches
     * user_recovery_codes: one lock order everywhere (users row, then codes)
     * rules out deadlocks (SDS idp-mfa.md §3.5). Must run inside a transaction.
     */
    public function lockUserRow(User $user): void;

    /**
     * Identifier of the user's current batch: MIN(id) over all of the user's
     * rows (used and unused), or null when the user has none. Regeneration
     * deletes every previous row under the user lock, so this always identifies
     * the current batch. Flushes pending inserts first so a batch created in the
     * caller's transaction is visible.
     */
    public function getCurrentBatchId(User $user): ?int;
}
