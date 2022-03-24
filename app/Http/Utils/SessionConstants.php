<?php namespace App\Http\Utils;
/*
 * Copyright 2022 OpenStack Foundation
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

/**
 * Class SessionConstants
 * @package App\Http\Utils
 */
final class SessionConstants
{
    const BackUrl = 'backurl';
    const OpenIdAuthzResponse = 'openid.authorization.response';
    const OpenIdAuthResponse = 'openstackid.authentication.response';
    const OAuth2RequestState = 'oauth2.request.state';
    const UserIdParam = 'openstackid.oauth2.principal.user_id';
    const AuthTimeParam = 'openstackid.oauth2.principal.auth_time';
    const OPBrowserState = 'openstackid.oauth2.principal.opbs';
    const RequestedUserIdParam = 'openstackid.oauth2.security_context.requested_user_id';
    const RequestedAuthTime    = 'openstackid.oauth2.security_context.requested_auth_time';
    const OpenIdRequestState = 'openid.request.state';
    const OpenIdAuthContext = 'openid.auth.context';

    const UserName = 'username';
    const UserFullName = 'user_fullname';
    const UserPic = 'user_pic';
    const UserVerified = 'user_verified';
}