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
use OAuth2\Models\SecurityContext;
use OAuth2\Services\ISecurityContextService;
use Illuminate\Support\Facades\Session;
use App\Http\Utils\SessionConstants;
/**
 * Class SecurityContextService
 * @package Services\OAuth2
 */
final class SecurityContextService implements ISecurityContextService
{

    /**
     * @return SecurityContext
     */
    public function get()
    {
        $context = new SecurityContext;

        $context->setState
        (
            array
            (
                Session::get(SessionConstants::RequestedUserIdParam),
                Session::get(SessionConstants::RequestedAuthTime),
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
        Session::put(SessionConstants::RequestedUserIdParam, $security_context->getRequestedUserId());
        Session::put(SessionConstants::RequestedAuthTime, $security_context->isAuthTimeRequired());
        Session::save();
        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        Session::remove(SessionConstants::RequestedUserIdParam);
        Session::remove(SessionConstants::RequestedAuthTime);
        Session::save();
        return $this;
    }
}