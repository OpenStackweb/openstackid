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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Class TwoFactorRateLimitMiddleware
 *
 * Throttles the MFA verify / recovery / resend endpoints. Counter state and
 * window logic live in ITwoFactorRateLimitService (shared with
 * UserController::postLogin(), whose initial challenge issuance counts
 * against the same resend window - SDS idp-mfa.md §4.12).
 *
 *  - verify / recovery: increment ONLY on a failed response.
 *  - resend:            increment on EVERY request.
 *
 * @package App\Http\Middleware
 */
final class TwoFactorRateLimitMiddleware
{
    private const PENDING_USER_KEY = '2fa_pending_user_id';

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
     * @param string $action one of verify|recovery|resend
     * @return mixed
     */
    public function handle($request, Closure $next, string $action = ITwoFactorRateLimitService::ActionVerify)
    {
        $subject = $action === ITwoFactorRateLimitService::ActionOtp
            ? $this->resolveOtpSubject($request)
            : $this->resolveSessionSubject();

        // Without a resolvable subject there is nothing to throttle; let the
        // controller resolve the (missing) state itself - mfa_session_expired
        // for the session-keyed actions, or its own validator 412 for otp.
        if (is_null($subject)) {
            return $next($request);
        }

        if ($this->rate_limit_service->isRateLimited($action, $subject)) {
            Log::debug(sprintf("TwoFactorRateLimitMiddleware: action %s subject %s rate limited", $action, $subject));
            return Response::json(
                [
                    'error_code'    => ITwoFactorRateLimitService::RATE_LIMIT_ERROR_CODE,
                    'error_message' => ITwoFactorRateLimitService::RATE_LIMIT_MESSAGE,
                ],
                HttpResponse::HTTP_TOO_MANY_REQUESTS
            )->withHeaders([
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
     * Session-keyed subject for verify/recovery/resend - the user id of the
     * pending MFA challenge.
     * @return int|null
     */
    private function resolveSessionSubject(): ?int
    {
        $userId = Session::get(self::PENDING_USER_KEY);
        return is_null($userId) ? null : (int) $userId;
    }

    /**
     * Subject for the anonymous, pre-auth otp action - the submitted email,
     * canonicalized the same way the case-insensitive users.email lookup
     * (DoctrineUserRepository::getByEmailOrName()) already resolves it, so the
     * rate-limit subject can't be reset by cycling the target email's casing.
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    private function resolveOtpSubject($request): ?string
    {
        $username = strtolower(trim($request->input('username', '')));
        return $username === '' ? null : $username;
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
