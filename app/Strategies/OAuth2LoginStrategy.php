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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\ISecurityContextService;
use Services\IUserActionService;
use Utils\IPHelper;
use Utils\Services\IAuthService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
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
     * @var ISecurityContextService
     */
    private $security_context_service;

    /**
     * @param IAuthService $auth_service
     * @param IMementoOAuth2SerializerService $memento_service
     * @param IUserActionService $user_action_service
     * @param ISecurityContextService $security_context_service
     */
    public function __construct
    (
        IAuthService $auth_service,
        IMementoOAuth2SerializerService $memento_service,
        IUserActionService $user_action_service,
        ISecurityContextService $security_context_service
    )
    {
        parent::__construct($user_action_service, $auth_service);
        $this->memento_service          = $memento_service;
        $this->security_context_service = $security_context_service;
    }

    public function getLogin()
    {
        Log::debug(sprintf("OAuth2LoginStrategy::getLogin"));

        if (!Auth::guest())
            return Redirect::action("UserController@getProfile");

        $requested_user_id = $this->security_context_service->get()->getRequestedUserId();
        if (!is_null($requested_user_id)) {
            $userHint = $this->auth_service->getUserById($requested_user_id);
            if (!is_null($userHint)) {
                Log::debug(sprintf("OAuth2LoginStrategy::getLogin user %s has saved state", $requested_user_id));
                Session::put('username', $userHint->getEmail());
                Session::put('user_fullname', $userHint->getFullName());
                Session::put('user_pic', $userHint->getPic());
                Session::put('user_verified', true);
                Session::save();
            }
        }

        $auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build(
            OAuth2Message::buildFromMemento(
                $this->memento_service->load()
            )
        );

        $response_strategy = DisplayResponseStrategyFactory::build($auth_request->getDisplay());

        return $response_strategy->getLoginResponse([
            'provider' => $auth_request instanceof OAuth2AuthenticationRequest ? $auth_request->getProvider() : null
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