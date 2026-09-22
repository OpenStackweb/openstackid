<?php
namespace Tests\unit;
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

use App\Http\Middleware\TwoFactorRateLimitMiddleware;
use App\Services\Auth\ITwoFactorRateLimitService;
use App\Services\Auth\TwoFactorRateLimitService;
use Auth\MFAConstants;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Regression: the middleware checked isRateLimited(), ran the controller and
 * only then incremented, so concurrent verify requests (sessions are not
 * locked) all passed the check and exceeded max_attempts. The attempt must be
 * reserved atomically before the controller runs.
 */
final class TwoFactorRateLimitMiddlewareTest extends TestCase
{
    private const SUBJECT = 42;

    private TwoFactorRateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();
        $app = new Container();
        $app->instance('config', new ConfigRepository([
            'two_factor' => ['rate_limit' => ['max_attempts' => 1, 'window_seconds' => 900]],
        ]));
        $app->instance(RateLimiter::class, new RateLimiter(new CacheRepository(new ArrayStore())));
        $app->instance('log', Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing());
        Facade::setFacadeApplication($app);

        $service = new TwoFactorRateLimitService();
        $app->make(RateLimiter::class)->for(
            ITwoFactorRateLimitService::RATE_LIMITER_NAME_PREFIX . ITwoFactorRateLimitService::ActionVerify,
            fn () => (new Limit(self::SUBJECT, $service->getLimit(ITwoFactorRateLimitService::ActionVerify)))
                ->response(fn ($request, array $headers) => new JsonResponse(
                    ['error_code' => MFAConstants::ERROR_CODE_RATE_LIMIT], 429, $headers
                ))
        );

        $this->middleware = new TwoFactorRateLimitMiddleware($service);
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
            parent::tearDown();
        }
    }

    public function testConcurrentVerifyRequestsCannotExceedMaxAttempts(): void
    {
        $concurrentResponse = null;
        $concurrentReachedController = false;

        // Request B arrives while request A is still inside the controller -
        // the interleaving two parallel requests produce.
        $response = $this->verify(function () use (&$concurrentResponse, &$concurrentReachedController) {
            $concurrentResponse = $this->verify(function () use (&$concurrentReachedController) {
                $concurrentReachedController = true;
                return $this->failure();
            });
            return $this->failure();
        });

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($concurrentReachedController, 'a concurrent request must not reach the controller past max_attempts');
        $this->assertSame(429, $concurrentResponse->getStatusCode());
    }

    public function testSuccessfulVerifyDoesNotConsumeAnAttempt(): void
    {
        $this->assertSame(200, $this->verify(fn () => new JsonResponse(['redirect_url' => '/']))->getStatusCode());

        // max_attempts=1: the failure still gets through because the success was refunded...
        $this->assertSame(401, $this->verify(fn () => $this->failure())->getStatusCode());
        // ...and that failure is what exhausts the window.
        $this->assertSame(429, $this->verify(fn () => $this->failure())->getStatusCode());
    }

    private function verify(\Closure $controller)
    {
        return $this->middleware->handle(Request::create('/auth/login/2fa/verify', 'POST'), $controller, ITwoFactorRateLimitService::ActionVerify);
    }

    private function failure(): JsonResponse
    {
        return new JsonResponse(['error_code' => MFAConstants::ERROR_CODE_VERIFICATION_FAILED], 401);
    }
}
