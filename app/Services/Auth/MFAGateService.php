<?php

namespace App\Services\Auth;

/**
 * Copyright 2025 OpenStack Foundation
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

/**
 * Class MFAGateService
 * @package App\Services\Auth
 */
final class MFAGateService implements ITwoFactorGateService
{
    public function __construct(
        private readonly IDeviceTrustService $deviceTrustService
    ) {
    }

    public function requiresChallenge(User $user, ?string $cookieToken): bool
    {
        // shouldRequire2FA() is the single source of truth for enforcement,
        // including the global kill-switch (config('two_factor.enabled'),
        // SDS idp-mfa.md §10.1) - so the passwordless-login guard, which also
        // calls shouldRequire2FA(), stays consistent with this gate.
        if (!$user->shouldRequire2FA()) {
            return false;
        }
        return !$this->deviceTrustService->isDeviceTrusted($user, $cookieToken);
    }
}
