<?php namespace Strategies;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\libs\OAuth2\Strategies\ILoginHintProcessStrategy;
use App\Services\Auth\IUserService;
use Illuminate\Support\Facades\Log;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\ISecurityContextService;
use OpenId\Services\IMementoOpenIdSerializerService;
use Services\IUserActionService;
use Utils\Services\IAuthService;

/**
 * Class LoginStrategyFactory
 * @package Strategies
 */
final class LoginStrategyFactory implements ILoginStrategyFactory
{
    /**
     * @var IMementoOpenIdSerializerService
     */
    private $openid_memento_service;
    /**
     * @var IMementoOAuth2SerializerService
     */
    private $oauth2_memento_service;
    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var IUserService
     */
    private $user_service;

    /**
     * @var IUserActionService
     */
    private $user_action_service;

    /**
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * @var ILoginHintProcessStrategy
     */
    private $login_hint_process_strategy;

    /**
     * @param IMementoOpenIdSerializerService $openid_memento_service
     * @param IMementoOAuth2SerializerService $oauth2_memento_service
     * @param IAuthService $auth_service
     * @param IUserService $user_service
     * @param IUserActionService $user_action_service
     * @param ISecurityContextService $security_context_service
     * @param ILoginHintProcessStrategy $login_hint_process_strategy
     */
    public function __construct
    (
        IMementoOpenIdSerializerService $openid_memento_service,
        IMementoOAuth2SerializerService $oauth2_memento_service,
        IAuthService $auth_service,
        IUserService $user_service,
        IUserActionService $user_action_service,
        ISecurityContextService $security_context_service,
        ILoginHintProcessStrategy $login_hint_process_strategy
    )
    {
        $this->openid_memento_service = $openid_memento_service;
        $this->oauth2_memento_service = $oauth2_memento_service;
        $this->auth_service = $auth_service;
        $this->user_service = $user_service;
        $this->user_action_service = $user_action_service;
        $this->security_context_service = $security_context_service;
        $this->login_hint_process_strategy = $login_hint_process_strategy;
    }

    public function build():ILoginStrategy{
        $res = null;
        Log::debug(sprintf("LoginStrategyFactory::build"));
        if ($this->openid_memento_service->exists())
        {
            //openid stuff
            Log::debug(sprintf("LoginStrategyFactory::build OIDC"));
            return new OpenIdLoginStrategy
            (
                $this->openid_memento_service,
                $this->user_action_service,
                $this->auth_service,
                $this->login_hint_process_strategy
            );

        }
        else if ($this->oauth2_memento_service->exists())
        {
            Log::debug(sprintf("LoginStrategyFactory::build OAUTH2"));
            return new OAuth2LoginStrategy
            (
                $this->auth_service,
                $this->oauth2_memento_service,
                $this->user_action_service,
                $this->login_hint_process_strategy
            );
        }
        //default stuff
        Log::debug(sprintf("LoginStrategyFactory::build DEFAULT"));
        return new DefaultLoginStrategy
        (
            $this->user_action_service,
            $this->auth_service,
            $this->login_hint_process_strategy
        );
    }
}