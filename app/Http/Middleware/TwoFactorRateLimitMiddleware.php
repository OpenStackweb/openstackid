<?php namespace App\Http\Middleware;
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

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Class TwoFactorRateLimitMiddleware
 *
 * Throttles the MFA verify / recovery / resend endpoints. Counters are stored in
 * the cache (NOT the session) so they survive session cleanup and keep an
 * independent, fixed-window TTL.
 *
 *  - verify / recovery: increment ONLY on a failed response.
 *  - resend:            increment on EVERY request.
 *
 * @package App\Http\Middleware
 */
final class TwoFactorRateLimitMiddleware
{
    public const ActionVerify   = 'verify';
    public const ActionRecovery = 'recovery';
    public const ActionResend   = 'resend';

    private const PENDING_USER_KEY = '2fa_pending_user_id';

    /**
     * Response error_code values that count as a verification failure.
     */
    private const FAILURE_CODES = [
        'mfa_verification_failed',
        'mfa_invalid_recovery',
    ];

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param string $action one of verify|recovery|resend
     * @return mixed
     */
    public function handle($request, Closure $next, string $action = self::ActionVerify)
    {
        $userId = Session::get(self::PENDING_USER_KEY);

        // Without a pending user there is nothing to throttle; let the controller
        // resolve the (missing) state and return mfa_session_expired.
        if (is_null($userId)) {
            return $next($request);
        }

        [$maxAttempts, $windowSeconds] = $this->limitsFor($action);
        $key = $this->cacheKey($action, $userId);

        if ((int) Cache::get($key, 0) >= $maxAttempts) {
            Log::debug(sprintf("TwoFactorRateLimitMiddleware: action %s user %s rate limited", $action, $userId));
            return Response::json(
                [
                    'error_code'    => 'mfa_rate_limit',
                    'error_message' => 'Too many attempts. Please try again later.',
                ],
                HttpResponse::HTTP_TOO_MANY_REQUESTS
            );
        }

        $response = $next($request);

        if ($action === self::ActionResend) {
            $this->increment($key, $windowSeconds);
        } else if ($this->isFailure($response)) {
            $this->increment($key, $windowSeconds);
        }

        return $response;
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

    private function cacheKey(string $action, $userId): string
    {
        return sprintf('2fa_rate:%s:%s', $action, $userId);
    }

    /**
     * Increment within a fixed window: add() sets the TTL once (only if the key
     * is absent), increment() bumps the value while preserving that TTL, so the
     * window starts at the first hit and does not slide.
     */
    private function increment(string $key, int $windowSeconds): void
    {
        Cache::add($key, 0, $windowSeconds);
        Cache::increment($key);
    }

    /**
     * @param mixed $response
     * @return bool
     */
    private function isFailure($response): bool
    {
        $content = method_exists($response, 'getContent') ? $response->getContent() : null;
        if (empty($content)) {
            return false;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded) || !isset($decoded['error_code'])) {
            return false;
        }

        return in_array($decoded['error_code'], self::FAILURE_CODES, true);
    }
}
