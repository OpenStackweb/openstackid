<?php namespace Tests\unit;
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

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use OAuth2\Models\SecurityContext;
use OAuth2\Services\ISecurityContextService;
use Tests\TestCase;
use Utils\Services\IAuthService;

/**
 * Regression tests for AuthService::logout()'s clear_security_ctx contract.
 *
 * The Session::flush() hardening added in #118 wipes the whole session,
 * including the session-backed security context - even when the caller asked
 * to keep it (clear_security_ctx = false, the prompt=login re-authentication
 * path in InteractiveGrantType::mustAuthenticateUser(), which relies on the
 * preserved requested-user id to show the login hint on the login screen).
 *
 * @package Tests\unit
 */
final class AuthServiceLogoutTest extends TestCase
{
    private const REQUESTED_USER_ID = 123;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    private function saveSecurityContext(): void
    {
        App::make(ISecurityContextService::class)->save(
            (new SecurityContext)
                ->setRequestedUserId(self::REQUESTED_USER_ID)
                ->setAuthTimeRequired(true)
        );
    }

    public function testLogoutPreservingSecurityContext_survivesSessionFlush(): void
    {
        $this->saveSecurityContext();
        Session::put('unrelated_key', 'value');

        App::make(IAuthService::class)->logout(false);

        $ctx = App::make(ISecurityContextService::class)->get();
        $this->assertSame(
            self::REQUESTED_USER_ID,
            $ctx->getRequestedUserId(),
            'logout(clear_security_ctx: false) must preserve the security context across the session flush'
        );
        $this->assertTrue($ctx->isAuthTimeRequired());
        // The flush hardening itself must still hold for everything else.
        $this->assertNull(Session::get('unrelated_key'), 'all other session data must still be flushed on logout');
    }

    public function testLogoutClearingSecurityContext_removesIt(): void
    {
        $this->saveSecurityContext();

        App::make(IAuthService::class)->logout(true);

        $ctx = App::make(ISecurityContextService::class)->get();
        $this->assertNull($ctx->getRequestedUserId(), 'logout(clear_security_ctx: true) must clear the security context');
    }
}
