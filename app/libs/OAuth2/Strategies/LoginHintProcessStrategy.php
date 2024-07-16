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
        $login_hint = null;
        if(!is_null($ctx)) {
            $login_hint = $ctx->getRequestedUserId();
        }

        if(empty($login_hint)){
            Log::debug
            (
                sprintf
                (
                    "LoginHintProcessStrategy::getLoginHint no login hint in security context, trying to get from QS"
                )
            );

            if(Request::has(OAuth2Protocol::OAuth2Protocol_LoginHint)) {
                $login_hint = Request::query(OAuth2Protocol::OAuth2Protocol_LoginHint);
                Log::debug(sprintf("LoginHintProcessStrategy::getLoginHint login_hint %s from QS", $login_hint));
                if (!EmailUtils::isValidEmail($login_hint)) {
                    Log::debug
                    (
                        sprintf
                        (
                            "LoginHintProcessStrategy::getLoginHint login_hint %s is not a valid email",
                            $login_hint
                        )
                    );

                    $login_hint = null;
                }
            }
        }

        return $login_hint;
    }

    public function process():void{
        $login_hint = $this->getLoginHint();
        Log::debug(sprintf("LoginHintProcessStrategy::process login_hint %s", $login_hint));
        // login hint processing

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
                Session::flash('username', $user->getEmail());
                Session::flash('user_fullname', $user->getFullName());
                Session::flash('user_pic', $user->getPic());
                Session::flash('user_verified', true);
            }
        }
    }
}