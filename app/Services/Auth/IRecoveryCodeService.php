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
     * @param User $user
     * @param string $currentPassword
     * @return string[] plaintext codes formatted as XXXX-XXXX
     * @throws ValidationException if $currentPassword does not match the user's password
     */
    public function regenerateRecoveryCodes(User $user, string $currentPassword): array;

    /**
     * Invalidates every existing recovery code for the user and generates a fresh
     * batch, without requiring password confirmation. Intended for first-time
     * generation right after 2FA enrollment, where the user's identity is already
     * established by the current session.
     *
     * @param User $user
     * @return string[] plaintext codes formatted as XXXX-XXXX
     */
    public function generateRecoveryCodes(User $user): array;

    /**
     * Enrolls the user into the given 2FA method and generates the first batch
     * of recovery codes for them, without requiring password confirmation.
     * Intended for enrollment via an already-authenticated session.
     *
     * @param User $user
     * @param string $method
     * @return string[] plaintext codes formatted as XXXX-XXXX
     * @throws ValidationException if $method is not a valid/enabled 2FA method
     */
    public function enableTwoFactorAndGenerateCodes(User $user, string $method): array;

    /**
     * @param User $user
     * @return int count of unused recovery codes
     */
    public function countUnusedRecoveryCodes(User $user): int;

    /**
     * @param User $user
     * @return RecoveryCodesStatus remaining/total/low-threshold standing for the user
     */
    public function getStatus(User $user): RecoveryCodesStatus;
}
