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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

/**
 * Verifies that an MFA challenge issued mid-OAuth2-authorization-flow gets
 * the response shape appropriate to the request's display mode: JSON+412
 * for native clients (matching every other login error for that mode),
 * redirect+session-flash for page/popup/touch (matching DefaultLoginStrategy
 * and errorLogin(), since both render the same login.js SPA via a native
 * form POST).
 *
 * @package Tests
 */
final class OAuth2NativeMFALoginFlowTest extends OpenStackIDBaseTestCase
{
    private const ADMIN_EMAIL   = 'sebastian@tipit.net';
    private const SEED_PASSWORD = '1Qaz2wsx!';
    private const CLIENT_ID     = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        Session::start();
    }

    public function testNativeClientReceives412OnMFARequired(): void
    {
        $this->authorize('native');

        $response = $this->postLoginPassword();

        $this->assertResponseStatus(412);
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('mfa_required', $payload['error_code']);
    }

    public function testNonNativeClientReceives302RedirectOnMFARequired(): void
    {
        // No display param -> defaults to the non-native (page/popup/touch)
        // display strategy.
        $this->authorize();

        $response = $this->postLoginPassword();

        $this->assertResponseStatus(302, 'must redirect like every other login outcome, not return JSON');
        $this->assertSame('2fa', Session::get('flow'), 'the redirected page must be able to restore the 2FA screen');
    }

    /**
     * Unauthenticated authorize request - the grant serializes the OAuth2
     * memento and hands off to the login flow.
     */
    private function authorize(?string $display = null)
    {
        $params = [
            'client_id'     => self::CLIENT_ID,
            'redirect_uri'  => 'https://www.test.com:443/oauth2?param=1&BackUrl=123344',
            'response_type' => 'code',
            'scope'         => sprintf('%s/resource-server/read', Config::get('app.url')),
        ];
        if (!is_null($display)) {
            $params['display'] = $display;
        }

        return $this->action('POST', 'OAuth2\OAuth2ProviderController@auth', $params);
    }

    /**
     * Submits the enforced-2FA admin's password within the same session -
     * this is what postLogin() sees as an OAuth2-originated login attempt
     * via the persisted memento.
     */
    private function postLoginPassword()
    {
        return $this->action('POST', 'UserController@postLogin', [
            'username' => self::ADMIN_EMAIL,
            'password' => self::SEED_PASSWORD,
            'flow'     => 'password',
            '_token'   => Session::token(),
        ]);
    }
}
