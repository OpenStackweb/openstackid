<?php namespace App\libs\OAuth2\Strategies;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\libs\Utils\EmailUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use OAuth2\OAuth2Protocol;
use OAuth2\Services\ISecurityContextService;
use Utils\Services\IAuthService;

/**
 * Class LoginHintProcessStrategy
 * @package App\libs\OAuth2\Strategies
 */
final class LoginHintProcessStrategy implements ILoginHintProcessStrategy
{
    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * @param IAuthService $auth_service
     * @param ISecurityContextService $security_context_service
     */
    public function __construct(IAuthService $auth_service, ISecurityContextService $security_context_service)
    {
        $this->auth_service = $auth_service;
        $this->security_context_service = $security_context_service;
    }

    /**
     * @return int|string|null
     */
    private function getLoginHint(){
        $ctx = $this->security_context_service->get();
        $Login_hint = null;
        if(!is_null($ctx)) {
            $Login_hint = $ctx->getRequestedUserId();
        }

        if(is_null($Login_hint)){
            if(Request::has(OAuth2Protocol::OAuth2Protocol_LoginHint)) {
                $login_hint = Request::query(OAuth2Protocol::OAuth2Protocol_LoginHint);
                if (!EmailUtils::isValidEmail($login_hint))
                    $login_hint = null;
            }
        }

        return $Login_hint;
    }

    public function process():void{
        $login_hint = $this->getLoginHint();
        // login hint processing
        Session::forget(['username', 'user_fullname', 'user_pic', 'user_verified']);
        if (!is_null($login_hint)) {
            $user = null;

            if(is_numeric($login_hint)) {
                Log::debug
                (
                    sprintf
                    (
                        "LoginHintProcessStrategy::process trying to get user using numeric login hint %s",
                        $login_hint
                    )
                );
                $user = $this->auth_service->getUserById(intval($login_hint));
            }

            if(is_null($user) && is_string($login_hint)) {
                Log::debug
                (
                    sprintf
                    (
                        "LoginHintProcessStrategy::process trying to get user using string login hint %s",
                        $login_hint
                    )
                );
                $user = $this->auth_service->getUserByUsername($login_hint);
            }

            if (!is_null($user)) {
                Log::debug(sprintf("LoginHintProcessStrategy::process user %s has saved state", $login_hint));
                Session::put('username', $user->getEmail());
                Session::put('user_fullname', $user->getFullName());
                Session::put('user_pic', $user->getPic());
                Session::put('user_verified', true);
            }
        }
        Session::save();
    }
}