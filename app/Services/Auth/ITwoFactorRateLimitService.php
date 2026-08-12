<?php

namespace App\Services\Auth;

use Auth\MFAConstants;

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
 * Also the single source of truth for the pending-user session key, shared
 * with the named RateLimiter::for() limiters registered in
 * TwoFactorServiceProvider that resolve the subject (Limit::by()) and the
 * 429 response shape (Limit::response()) for these actions.
 *
 * @package App\Services\Auth
 */
interface ITwoFactorRateLimitService
{
    public const ActionVerify   = 'verify';
    public const ActionRecovery = 'recovery';
    public const ActionResend   = 'resend';
    public const ActionOtp      = 'otp';

    public const RATE_LIMIT_ERROR_CODE = MFAConstants::ERROR_CODE_RATE_LIMIT;
    public const RATE_LIMIT_MESSAGE    = 'Too many attempts. Please try again later.';

    /**
     * Session key holding the user id of the pending MFA challenge - the
     * subject the verify/recovery/resend named limiters throttle by.
     */
    public const PENDING_USER_SESSION_KEY = MFAConstants::SESSION_KEY_PENDING_USER_ID;

    /**
     * Prefix applied to the Action* constants when registering/looking up
     * the named RateLimiter::for() limiters (TwoFactorServiceProvider /
     * TwoFactorRateLimitMiddleware). RateLimiter::for() names are a single
     * global namespace shared with RouteServiceProvider - notably its own
     * 'otp' limiter - so the bare action string ('otp') can't be reused
     * directly as the limiter name without silently clobbering it.
     */
    public const RATE_LIMITER_NAME_PREFIX = '2fa-rate:';

    /**
     * Prefix of the cache keys holding the per-subject attempt counters
     * (and their companion ":timer" keys) - see cacheKey() in the
     * implementation. Distinct from RATE_LIMITER_NAME_PREFIX (dash), which
     * names the limiters, not the storage.
     */
    public const RATE_LIMIT_CACHE_KEY_PREFIX = '2fa_rate:';

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

    /**
     * The configured max-attempts ceiling for this action, for the
     * X-RateLimit-Limit response header.
     * @param string $action one of self::ActionVerify|ActionRecovery|ActionResend|ActionOtp
     * @return int
     */
    public function getLimit(string $action): int;

    /**
     * The configured window length for this action, in seconds - used to
     * build the matching RateLimiter::for() Limit definition in
     * TwoFactorServiceProvider so its decaySeconds stays in sync with the
     * value this service actually enforces.
     * @param string $action one of self::ActionVerify|ActionRecovery|ActionResend|ActionOtp
     * @return int
     */
    public function getWindowSeconds(string $action): int;

    /**
     * Seconds remaining until this subject's current window resets, for the
     * Retry-After response header. Returns 0 if there is no active window.
     * @param string $action one of self::ActionVerify|ActionRecovery|ActionResend|ActionOtp
     * @param string|int $subject a user id for session-keyed actions, or a raw
     *                            (already-canonicalized) subject string for ActionOtp
     * @return int
     */
    public function getRetryAfterSeconds(string $action, string|int $subject): int;
}
