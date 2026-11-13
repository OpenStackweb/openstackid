<?php namespace Tests;
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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Regression: the session-authenticated 2FA profile endpoints change account
 * security state, but the admin/api/v1 group only applies ssl+auth and the
 * web group has no CSRF check - so a cross-site form POST (session cookie is
 * SameSite=None) could enable 2FA for a logged-in user.
 *
 * VerifyCsrfToken skips itself while running unit tests, so an HTTP-level
 * test cannot observe the rejection; the route's middleware stack is asserted
 * instead.
 *
 * @package Tests
 */
final class TwoFactorProfileRoutesCsrfTest extends TestCase
{
    /**
     * @dataProvider stateChangingRoutes
     */
    public function testTwoFactorProfileRouteRequiresCsrf(string $uri, string $method = 'POST'): void
    {
        $route = Route::getRoutes()->match(Request::create($uri, $method));

        $this->assertContains('csrf', $route->gatherMiddleware());
    }

    public static function stateChangingRoutes(): array
    {
        return [
            'enable 2fa'                 => ['/admin/api/v1/users/me/2fa/enable'],
            'regenerate recovery codes'  => ['/admin/api/v1/users/me/recovery-codes/regenerate'],
            'revoke trusted device'      => ['/admin/api/v1/users/me/2fa/devices/1', 'DELETE'],
            'revoke all trusted devices' => ['/admin/api/v1/users/me/2fa/devices', 'DELETE'],
        ];
    }
}
