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

use Auth\Exceptions\AuthenticationException;
use Auth\User;

/**
 * Interface ITwoFactorChallengeService
 *
 * Single source of truth for issuing the local MFA challenge on a primary
 * authentication that just succeeded (password or social login) - shared
 * so every primary-auth flow that must honor User::shouldRequire2FA()
 * applies the exact same challenge (rate limit, OTP issuance, audit log,
 * session bookkeeping) instead of drifting out of sync.
 *
 * @package App\Services\Auth
 */
interface ITwoFactorChallengeService
{
    /**
     * @param User $user the user who just completed primary authentication
     * @param string|null $cookieToken the device-trust cookie value, if any
     * @param bool $remember
     * @return array|null null when no challenge is required (the caller should
     *                     complete the login); otherwise the challenge payload
     *                     the caller should pass to login_strategy->challengeRequired()
     * @throws AuthenticationException when the resend rate limit is exceeded
     */
    public function issueChallengeIfRequired(User $user, ?string $cookieToken, bool $remember): ?array;
}
