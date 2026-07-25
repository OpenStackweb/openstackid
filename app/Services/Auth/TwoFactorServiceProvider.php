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

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Class TwoFactorServiceProvider
 * @package App\Services\Auth
 */
final class TwoFactorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        $this->registerRateLimiters();
    }

    public function register(): void
    {
        $this->app->singleton(IDeviceTrustService::class, DeviceTrustService::class);
        $this->app->singleton(ITwoFactorAuditService::class, TwoFactorAuditService::class);
        $this->app->singleton(ITwoFactorGateService::class, MFAGateService::class);
        $this->app->singleton(ITwoFactorRateLimitService::class, TwoFactorRateLimitService::class);
    }

    /**
     * Named RateLimiter::for() limiters for the 2FA actions - own the two
     * things that vanilla ->middleware('throttle:...') can declare: the
     * throttled subject (Limit::by()) and the 429 response shape
     * (Limit::response()). TwoFactorRateLimitMiddleware still enforces the
     * limit itself and decides *when* to count a hit, because the stock
     * throttle pipeline always increments before the request reaches the
     * controller and has no hook for "only count on failure" - required for
     * verify/recovery per SDS idp-mfa.md §4.12. Returning Limit::none()
     * signals "no resolvable subject yet" so the middleware lets the request
     * through and the controller resolves the (missing) state itself.
     */
    private function registerRateLimiters(): void
    {
        $rateLimitService = $this->app->make(ITwoFactorRateLimitService::class);

        $respondRateLimited = fn ($request, array $headers) => Response::json(
            [
                'error_code'    => ITwoFactorRateLimitService::RATE_LIMIT_ERROR_CODE,
                'error_message' => ITwoFactorRateLimitService::RATE_LIMIT_MESSAGE,
            ],
            HttpResponse::HTTP_TOO_MANY_REQUESTS
        )->withHeaders($headers);

        $bySessionPendingUser = function (string $action) use ($rateLimitService, $respondRateLimited) {
            $userId = Session::get(ITwoFactorRateLimitService::PENDING_USER_SESSION_KEY);

            if (is_null($userId)) {
                return Limit::none();
            }

            return (new Limit(
                (int) $userId,
                $rateLimitService->getLimit($action),
                $rateLimitService->getWindowSeconds($action)
            ))->response($respondRateLimited);
        };

        $limiterName = fn (string $action) => ITwoFactorRateLimitService::RATE_LIMITER_NAME_PREFIX . $action;

        RateLimiter::for($limiterName(ITwoFactorRateLimitService::ActionVerify), fn () => $bySessionPendingUser(ITwoFactorRateLimitService::ActionVerify));
        RateLimiter::for($limiterName(ITwoFactorRateLimitService::ActionRecovery), fn () => $bySessionPendingUser(ITwoFactorRateLimitService::ActionRecovery));
        RateLimiter::for($limiterName(ITwoFactorRateLimitService::ActionResend), fn () => $bySessionPendingUser(ITwoFactorRateLimitService::ActionResend));

        RateLimiter::for($limiterName(ITwoFactorRateLimitService::ActionOtp), function (Request $request) use ($rateLimitService, $respondRateLimited) {
            $username = strtolower(trim($request->input('username', '')));

            if ($username === '') {
                return Limit::none();
            }

            return (new Limit(
                $username,
                $rateLimitService->getLimit(ITwoFactorRateLimitService::ActionOtp),
                $rateLimitService->getWindowSeconds(ITwoFactorRateLimitService::ActionOtp)
            ))->response($respondRateLimited);
        });
    }

    public function provides(): array
    {
        return [
            IDeviceTrustService::class,
            ITwoFactorAuditService::class,
            ITwoFactorGateService::class,
            ITwoFactorRateLimitService::class,
        ];
    }
}
