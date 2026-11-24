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

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\OAuth2Protocol;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\IMementoOAuth2SerializerService;
use Utils\Services\IAuthService;

/**
 * Class OAuth2ConsentStrategy
 * @package Strategies
 */
class OAuth2ConsentStrategy implements IConsentStrategy
{
    /**
     * @var IAuthService
     */
    private $auth_service;
    /**
     * @var IMementoOAuth2SerializerService
     */
    private $memento_service;
    /**
     * @var IApiScopeRepository
     */
    private $scope_repository;
    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * OAuth2ConsentStrategy constructor.
     * @param IAuthService $auth_service
     * @param IMementoOAuth2SerializerService $memento_service
     * @param IApiScopeRepository $scope_repository
     * @param IClientRepository $client_repository
     */
    public function __construct
    (
        IAuthService $auth_service,
        IMementoOAuth2SerializerService $memento_service,
        IApiScopeRepository $scope_repository,
        IClientRepository $client_repository
    )
    {
        $this->auth_service      = $auth_service;
        $this->memento_service   = $memento_service;
        $this->scope_repository  = $scope_repository;
        $this->client_repository = $client_repository;
    }

    public function getConsent()
    {
        Log::debug("OAuth2ConsentStrategy::getConsent");

        $auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
        (
            OAuth2Message::buildFromMemento
            (
                $this->memento_service->load()
            )
        );

        Log::debug(sprintf("OAuth2ConsentStrategy::getConsent auth request %s", $auth_request->__toString()));


        $client_id = $auth_request->getClientId();
        $client = $this->client_repository->getClientById($client_id);
        $scopes = explode(' ', $auth_request->getScope());
        $requested_scopes = $this->scope_repository->getByNames($scopes);

        $data = [];
        $data['requested_scopes'] = $requested_scopes;
        $data['app_name'] = $client->getApplicationName();
        $data['redirect_to'] = $auth_request->getRedirectUri();
        $data['website'] = $client->getWebsite();
        $data['tos_uri'] = $client->getTermOfServiceUri();
        $data['policy_uri'] = $client->getPolicyUri();

        $app_logo = $client->getApplicationLogo();

        $data['app_logo'] = $app_logo;
        $data['app_description'] = $client->getApplicationDescription();
        $data['dev_info_email'] = $client->getDeveloperEmail();
        $data['contact_emails'] = $client->getContacts();
        $data['can_switch_account'] = !$this->isPinnedToUser($auth_request);

        $response_strategy = DisplayResponseStrategyFactory::build($auth_request->getDisplay());

        return $response_strategy->getConsentResponse($data);
    }

    public function postConsent($trust_action)
    {
        $this->auth_service->setUserAuthorizationResponse($trust_action);
        return Redirect::action('OAuth2\OAuth2ProviderController@auth');
    }

    /**
     * The RP asked for a specific user (login_hint / id_token_hint), so the user can't be swapped.
     * @param OAuth2AuthorizationRequest $auth_request
     * @return bool
     */
    private function isPinnedToUser(OAuth2AuthorizationRequest $auth_request): bool
    {
        return $auth_request instanceof OAuth2AuthenticationRequest &&
            (!empty($auth_request->getLoginHint()) || !empty($auth_request->getIdTokenHint()));
    }

    /**
     * Ends the current IDP session and sends the user to login, keeping the pending authorization request.
     * AuthService::logout() flushes the whole session, memento included, so it is re-serialized afterwards.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function switchAccount()
    {
        $memento = $this->memento_service->load();
        if (is_null($memento))
            return $this->badRequest();

        $auth_request = OAuth2AuthorizationRequestFactory::getInstance()->build
        (
            OAuth2Message::buildFromMemento($memento)
        );

        if ($this->isPinnedToUser($auth_request))
            return $this->badRequest();

        // the new user must actually see consent when the RP asked for prompt=consent;
        // prompt=login stays processed, otherwise the user who just logged in is logged out again
        if ($auth_request instanceof OAuth2AuthenticationRequest)
            $auth_request->unmarkParamAsProcessed(OAuth2Protocol::OAuth2Protocol_Prompt_Consent);

        $memento = $auth_request->getMessage()->createMemento();

        $this->auth_service->logout();
        $this->memento_service->serialize($memento);

        return Redirect::action('UserController@getLogin');
    }

    private function badRequest()
    {
        return Response::view
        (
            'errors.400',
            [
                'error' => "Bad Request",
                'error_description' => "Generic Error"
            ],
            400
        );
    }
}