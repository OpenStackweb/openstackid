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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Class TwoFactorRateLimitService
 * @package App\Services\Auth
 */
final class TwoFactorRateLimitService implements ITwoFactorRateLimitService
{
    public function isRateLimited(string $action, int $userId): bool
    {
        [$maxAttempts, ] = $this->limitsFor($action);
        return (int) Cache::get($this->cacheKey($action, $userId), 0) >= $maxAttempts;
    }

    public function increment(string $action, int $userId): void
    {
        [, $windowSeconds] = $this->limitsFor($action);
        $key = $this->cacheKey($action, $userId);

        // Fixed window: add() sets the TTL once (only if the key is absent),
        // increment() bumps the value while preserving that TTL, so the
        // window starts at the first hit and does not slide.
        Cache::add($key, 0, $windowSeconds);
        Cache::increment($key);
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

        return [
            (int) Config::get('two_factor.rate_limit.max_attempts', 3),
            (int) Config::get('two_factor.rate_limit.window_seconds', 900),
        ];
    }

    private function cacheKey(string $action, int $userId): string
    {
        return sprintf('2fa_rate:%s:%s', $action, $userId);
    }
}
