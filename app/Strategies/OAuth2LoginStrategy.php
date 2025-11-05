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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OAuth2\Endpoints\AuthorizationEndpoint;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Services\IMementoOAuth2SerializerService;
use Services\IUserActionService;
use Utils\IPHelper;
use Utils\Services\IAuthService;
use Illuminate\Support\Facades\Redirect;
/**
 * Class OAuth2LoginStrategy
 * @package Strategies
 */
class OAuth2LoginStrategy extends DefaultLoginStrategy
{

    /**
     * @var IMementoOAuth2SerializerService
     */
    private $memento_service;



    /**
     * @param IAuthService $auth_service
     * @param IMementoOAuth2SerializerService $memento_service
     * @param IUserActionService $user_action_service
     * @param ILoginHintProcessStrategy $login_hint_process_strategy
     */
    public function __construct
    (
        IAuthService $auth_service,
        IMementoOAuth2SerializerService $memento_service,
        IUserActionService $user_action_service,
        ILoginHintProcessStrategy $login_hint_process_strategy
    )
    {
        parent::__construct($user_action_service, $auth_service, $login_hint_process_strategy);
        $this->memento_service = $memento_service;
    }

    public function getLogin()
    {
        Log::debug("OAuth2LoginStrategy::getLogin");

        if (!Auth::guest())
            return Redirect::action("UserController@getProfile");

        $this->login_hint_process_strategy->process();

        $auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build(
            OAuth2Message::buildFromMemento(
                $this->memento_service->load()
            )
        );

        Log::debug("OAuth2LoginStrategy::getLogin", ['auth_request' => (string)$auth_request ]);

        $response_strategy = DisplayResponseStrategyFactory::build($auth_request->getDisplay());

        return $response_strategy->getLoginResponse([
            'provider' => $auth_request instanceof OAuth2AuthenticationRequest ? $auth_request->getProvider() : null,
            'tenant' => $auth_request instanceof OAuth2AuthorizationRequest ? $auth_request->getTenant() : null
        ]);
    }

    public function postLogin(array $params = [])
    {
        Log::debug(sprintf("OAuth2LoginStrategy::postLogin params %s", json_encode($params)));

        $auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build(
            OAuth2Message::buildFromMemento(
                $this->memento_service->load()
            )
        );

        $realm = "From ".$auth_request->getRedirectUri();
        if(isset($params['provider']))
            $realm .= " using ".strtoupper($params['provider']);

        $this->user_action_service->addUserAction($this->auth_service->getCurrentUser()->getId(), IPHelper::getUserIp(),
            IUserActionService::LoginAction, $realm);

        return Redirect::action("OAuth2\OAuth2ProviderController@auth");
    }

    public function cancelLogin()
    {
        $this->auth_service->setUserAuthenticationResponse(IAuthService::AuthenticationResponse_Cancel);

        return Redirect::action("OAuth2\OAuth2ProviderController@auth");
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function errorLogin(array $params)
    {
        $auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build(
            OAuth2Message::buildFromMemento(
                $this->memento_service->load()
            )
        );

        $response_strategy = DisplayResponseStrategyFactory::build($auth_request->getDisplay());

        return $response_strategy->getLoginErrorResponse($params);
    }
}