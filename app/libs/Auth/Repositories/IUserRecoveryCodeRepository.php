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
     */
    public function refreshExclusiveLock(UserRecoveryCode $code): void;

    /**
     * Delete every recovery code for a user (used when regenerating).
     */
    public function deleteAllForUser(User $user): int;
}
