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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Class TwoFactorRateLimitService
 * @package App\Services\Auth
 */
final class TwoFactorRateLimitService implements ITwoFactorRateLimitService
{
    public function isRateLimited(string $action, string|int $subject): bool
    {
        [$maxAttempts, ] = $this->limitsFor($action);
        return RateLimiter::tooManyAttempts($this->cacheKey($action, $subject), $maxAttempts);
    }

    public function increment(string $action, string|int $subject): void
    {
        [, $windowSeconds] = $this->limitsFor($action);

        // Fixed window: RateLimiter::hit() sets a companion "resets at" timer
        // key once (only if absent) and bumps the counter while preserving
        // that timer, so the window starts at the first hit and does not
        // slide - same semantics the previous hand-rolled Cache::add()+
        // increment() had, but this also gives us availableIn() for
        // Retry-After without a driver-specific TTL query.
        RateLimiter::hit($this->cacheKey($action, $subject), $windowSeconds);
    }

    public function getLimit(string $action): int
    {
        [$maxAttempts, ] = $this->limitsFor($action);
        return $maxAttempts;
    }

    public function getRetryAfterSeconds(string $action, string|int $subject): int
    {
        return RateLimiter::availableIn($this->cacheKey($action, $subject));
    }

    /**
     * @param string $action
     * @return array{0:int,1:int} [maxAttempts, windowSeconds]
     */
    private function limitsFor(string $action): array
    {
        if ($action === self::ActionResend) {
            return [
                (int) Config::get('two_factor.rate_limit.max_otp_requests', 5),
                (int) Config::get('two_factor.rate_limit.otp_window_minutes', 15) * 60,
            ];
        }

        if ($action === self::ActionOtp) {
            return [
                (int) Config::get('two_factor.rate_limit.max_otp_email_requests', 5),
                (int) Config::get('two_factor.rate_limit.otp_email_window_minutes', 15) * 60,
            ];
        }

        return [
            (int) Config::get('two_factor.rate_limit.max_attempts', 3),
            (int) Config::get('two_factor.rate_limit.window_seconds', 900),
        ];
    }

    /**
     * @param string $action
     * @param string|int $subject a user id for session-keyed actions, or a raw
     *                            (already-canonicalized) subject string for ActionOtp
     * @return string
     */
    private function cacheKey(string $action, string|int $subject): string
    {
        return sprintf('2fa_rate:%s:%s', $action, $subject);
    }
}
