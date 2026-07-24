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
 * Interface ITwoFactorRateLimitService
 *
 * Shared cache-backed rate-limit window for MFA verify/recovery/resend
 * actions. Single source of truth for the cache-key format and per-action
 * limits, used by both TwoFactorRateLimitMiddleware (verify/recovery/resend
 * routes) and UserController::postLogin() (initial challenge issuance,
 * which shares the resend window per SDS idp-mfa.md §4.12).
 *
 * @package App\Services\Auth
 */
interface ITwoFactorRateLimitService
{
    public const ActionVerify   = 'verify';
    public const ActionRecovery = 'recovery';
    public const ActionResend   = 'resend';
    public const ActionOtp      = 'otp';

    public const RATE_LIMIT_ERROR_CODE = 'mfa_rate_limit';
    public const RATE_LIMIT_MESSAGE    = 'Too many attempts. Please try again later.';

    /**
     * @param string $action one of self::ActionVerify|ActionRecovery|ActionResend|ActionOtp
     * @param string|int $subject a user id for session-keyed actions, or a raw
     *                            (already-canonicalized) subject string for ActionOtp
     * @return bool
     */
    public function isRateLimited(string $action, string|int $subject): bool;

    /**
     * @param string $action one of self::ActionVerify|ActionRecovery|ActionResend|ActionOtp
     * @param string|int $subject a user id for session-keyed actions, or a raw
     *                            (already-canonicalized) subject string for ActionOtp
     * @return void
     */
    public function increment(string $action, string|int $subject): void;
}
