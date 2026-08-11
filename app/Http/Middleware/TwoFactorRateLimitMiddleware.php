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

use App\Services\Auth\ITwoFactorRateLimitService;
use Closure;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Class TwoFactorRateLimitMiddleware
 *
 * Throttles the MFA verify / recovery / resend endpoints. Counter state and
 * window logic live in ITwoFactorRateLimitService (shared with
 * UserController::postLogin(), whose initial challenge issuance counts
 * against the same resend window - SDS idp-mfa.md §4.12). The throttled
 * subject and the 429 response shape are resolved via the named
 * RateLimiter::for() limiters registered in TwoFactorServiceProvider - this
 * middleware only decides *when* a hit counts, since the stock throttle
 * pipeline has no hook for that:
 *
 *  - verify / recovery: increment ONLY on a failed response.
 *  - resend / otp:      increment on EVERY request.
 *
 * @package App\Http\Middleware
 */
final class TwoFactorRateLimitMiddleware
{
    /**
     * Response error_code values that count as a verification failure.
     */
    private const FAILURE_CODES = [
        'mfa_verification_failed',
        'mfa_invalid_recovery',
    ];

    public function __construct(private readonly ITwoFactorRateLimitService $rate_limit_service)
    {
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param string $action one of verify|recovery|resend|otp
     * @return mixed
     */
    public function handle($request, Closure $next, string $action = ITwoFactorRateLimitService::ActionVerify)
    {
        $limiter = RateLimiter::limiter(ITwoFactorRateLimitService::RATE_LIMITER_NAME_PREFIX . $action);
        $limit = $limiter ? $limiter($request) : null;

        // No named limiter resolved a subject (no pending session / no otp
        // username submitted) - nothing to throttle; let the controller
        // resolve the (missing) state itself - mfa_session_expired for the
        // session-keyed actions, or its own validator 412 for otp.
        if (is_null($limit) || $limit instanceof Unlimited) {
            return $next($request);
        }

        $subject = $limit->key;

        if ($this->rate_limit_service->isRateLimited($action, $subject)) {
            Log::debug(sprintf("TwoFactorRateLimitMiddleware: action %s subject %s rate limited", $action, $subject));

            $responseCallback = $limit->responseCallback;
            return $responseCallback($request, [
                'Retry-After'           => $this->rate_limit_service->getRetryAfterSeconds($action, $subject),
                'X-RateLimit-Limit'     => $this->rate_limit_service->getLimit($action),
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $response = $next($request);

        if ($action === ITwoFactorRateLimitService::ActionResend || $action === ITwoFactorRateLimitService::ActionOtp) {
            $this->rate_limit_service->increment($action, $subject);
        } else if ($this->isFailure($response)) {
            $this->rate_limit_service->increment($action, $subject);
        }

        return $response;
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
