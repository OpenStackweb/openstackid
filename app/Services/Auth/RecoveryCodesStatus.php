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

/**
 * Immutable snapshot of a user's recovery-codes standing, built by
 * IRecoveryCodeService::getStatus(). toArray() owns the wire keys consumed by
 * the login SPA (resources/js/login/login.js) and the profile page
 * (resources/views/profile.blade.php) - CU-86ba2zp66 / sds/idp-mfa.md §4.10.3,
 * §4.11 step 5: the UI must be able to warn the user when they've burned into
 * their last few recovery codes, since those may be their only way back in.
 *
 * @package App\Services\Auth
 */
final class RecoveryCodesStatus
{
    public function __construct(
        private readonly int $remaining,
        private readonly int $total,
        private readonly int $low_threshold,
    ) {}

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getLowThreshold(): int
    {
        return $this->low_threshold;
    }

    /**
     * @return array<string,int>
     */
    public function toArray(): array
    {
        return [
            'recovery_codes_remaining'     => $this->remaining,
            'recovery_codes_total'         => $this->total,
            'recovery_codes_low_threshold' => $this->low_threshold,
        ];
    }
}
