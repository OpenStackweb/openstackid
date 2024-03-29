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

use Illuminate\Support\Facades\Log;
use OAuth2\Models\SecurityContext;
use OAuth2\Services\ISecurityContextService;
use Illuminate\Support\Facades\Session;

/**
 * Class SecurityContextService
 * @package Services\OAuth2
 */
final class SecurityContextService implements ISecurityContextService
{

    const RequestedUserIdParam = 'openstackid.oauth2.security_context.requested_user_id';
    const RequestedAuthTime    = 'openstackid.oauth2.security_context.requested_auth_time';
    /**
     * @return SecurityContext
     */
    public function get()
    {
        Log::debug("SecurityContextService::get");

        $context = new SecurityContext;

        $context->setState
        (
            array
            (
                Session::get(self::RequestedUserIdParam),
                Session::get(self::RequestedAuthTime),
            )
        );

        return $context;
    }

    /**
     * @param SecurityContext $security_context
     * @return $this
     */
    public function save(SecurityContext $security_context)
    {
        Log::debug("SecurityContextService::save");
        Session::put(self::RequestedUserIdParam, $security_context->getRequestedUserId());
        Session::put(self::RequestedAuthTime, $security_context->isAuthTimeRequired());
        Session::save();
        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        Log::debug("SecurityContextService::clear");
        Session::remove(self::RequestedUserIdParam);
        Session::remove(self::RequestedAuthTime);
        Session::save();
        return $this;
    }
}