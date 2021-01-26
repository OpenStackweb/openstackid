<?php namespace Services\OAuth2;
/**
 * Copyright 2016 OpenStack Foundation
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
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use OAuth2\Models\IPrincipal;
use OAuth2\Models\Principal;
use OAuth2\Services\IPrincipalService;

/**
 * Class PrincipalService
 * @package Services\OAuth2
 */
final class PrincipalService implements IPrincipalService
{

    const UserIdParam = 'openstackid.oauth2.principal.user_id';
    const AuthTimeParam = 'openstackid.oauth2.principal.auth_time';
    const OPBrowserState = 'openstackid.oauth2.principal.opbs';

    /**
     * @return IPrincipal
     */
    public function get()
    {
        $principal = new Principal;
        $user_id = Session::get(self::UserIdParam);
        $auth_time = Session::get(self::AuthTimeParam);
        $op_browser_state = Session::get(self::OPBrowserState);

        Log::debug(sprintf("PrincipalService::get - user_id %s auth_time %s op_browser_state %s", $user_id, $auth_time, $op_browser_state));

        // overwrite it just in case

        Cookie::queue
        (
            IPrincipalService::OP_BROWSER_STATE_COOKIE_NAME,
            $op_browser_state,
            Config::get("session.lifetime", 120),
            $path = Config::get("session.path"),
            $domain = Config::get("session.domain"),
            $secure = true,
            $httpOnly = false,
            $raw = false,
            $sameSite = 'none'
        );
        $principal->setState
        (
            [
                $user_id,
                $auth_time,
                $op_browser_state
            ]
        );

        return $principal;
    }

    /**
     * @param IPrincipal $principal
     * @return void
     */
    public function save(IPrincipal $principal)
    {
        Log::debug("PrincipalService::save");

        $this->register
        (
            $principal->getUserId(),
            $principal->getAuthTime()
        );
    }

    /**
     * @return string
     */
    private function calculateBrowserState(): string
    {
        return hash('sha256', Session::getId());
    }

    /**
     * @param int $user_id
     * @param int $auth_time
     * @return mixed
     */
    public function register($user_id, $auth_time)
    {
        Log::debug(sprintf("PrincipalService::register user_id %s auth_time %s", $user_id, $auth_time));
        Session::put(self::UserIdParam, $user_id);
        Session::put(self::AuthTimeParam, $auth_time);
        // Maintain a `op_browser_state` cookie along with the `sessionid` cookie that
        // represents the End-User's login state at the OP. If the user is not logged
        $op_browser_state = $this->calculateBrowserState();
        Cookie::queue
        (
            IPrincipalService::OP_BROWSER_STATE_COOKIE_NAME,
            $op_browser_state,
            Config::get("session.lifetime", 120),
            $path = Config::get("session.path"),
            $domain = Config::get("session.domain"),
            $secure = true,
            $httpOnly = false,
            $raw = false,
            $sameSite = 'none'
        );
        Log::debug(sprintf("PrincipalService::register op_browser_state %s", $op_browser_state));
        Session::put(self::OPBrowserState, $op_browser_state);
        Session::save();
    }

    /**
     * @return $this
     */
    public function clear()
    {
        Log::debug("PrincipalService::clear");
        Session::remove(self::UserIdParam);
        Session::remove(self::AuthTimeParam);
        Session::remove(self::OPBrowserState);
        Session::save();
        Cookie::queue
        (
            IPrincipalService::OP_BROWSER_STATE_COOKIE_NAME,
            null,
            $minutes = -2628000,
            $path = Config::get("session.path"),
            $domain = Config::get("session.domain"),
            $secure = true,
            $httpOnly = false,
            $raw = false,
            $sameSite = 'none'
        );
    }

}