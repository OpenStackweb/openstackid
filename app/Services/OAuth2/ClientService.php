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

use App\Events\OAuth2ClientLocked;
use App\Models\OAuth2\Factories\ClientFactory;
use App\Services\AbstractService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Models\OAuth2\ApiScope;
use Models\OAuth2\Client;
use models\utils\IEntity;
use OAuth2\Exceptions\InvalidClientAuthMethodException;
use OAuth2\Exceptions\MissingClientAuthorizationInfo;
use OAuth2\Models\ClientAssertionAuthenticationContext;
use OAuth2\Models\ClientAuthenticationContext;
use OAuth2\Models\ClientCredentialsAuthenticationContext;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientCredentialGenerator;
use OAuth2\Services\IClientService;
use models\exceptions\ValidationException;
use Utils\Db\ITransactionService;
use models\exceptions\EntityNotFoundException;
use Utils\Http\HttpUtils;
use Utils\Services\IAuthService;
/**
 * Class ClientService
 * @package Services\OAuth2
 */
final class ClientService extends AbstractService implements IClientService
{
    /**
     * @var IAuthService
     */
    private $auth_service;
    /**
     * @var IApiScopeService
     */
    private $scope_service;
    /**
     * @var IUserRepository
     */
    private $user_repository;
    /**
     * @var IClientCredentialGenerator
     */
    private $client_credential_generator;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IApiScopeRepository
     */
    private $scope_repository;

    /**
     * ClientService constructor.
     * @param IUserRepository $user_repository
     * @param IClientRepository $client_repository
     * @param IAuthService $auth_service
     * @param IApiScopeService $scope_service
     * @param IClientCredentialGenerator $client_credential_generator
     * @param IApiScopeRepository $scope_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository             $user_repository,
        IClientRepository           $client_repository,
        IAuthService                $auth_service,
        IApiScopeService            $scope_service,
        IClientCredentialGenerator  $client_credential_generator,
        IApiScopeRepository         $scope_repository,
        ITransactionService         $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->auth_service                = $auth_service;
        $this->user_repository             = $user_repository;
        $this->scope_service               = $scope_service;
        $this->client_credential_generator = $client_credential_generator;
        $this->client_repository           = $client_repository;
        $this->scope_repository            = $scope_repository;
    }


    /**
     * Clients in possession of a client password MAY use the HTTP Basic
     * authentication scheme as defined in [RFC2617] to authenticate with
     * the authorization server
     * Alternatively, the authorization server MAY support including the
     * client credentials in the request-body using the following
     * parameters:
     * implementation of @see http://tools.ietf.org/html/rfc6749#section-2.3.1
     * implementation of @see http://openid.net/specs/openid-connect-core-1_0.html#ClientAuthentication
     * @throws InvalidClientAuthMethodException
     * @throws MissingClientAuthorizationInfo
     * @return ClientAuthenticationContext
     */
    public function getCurrentClientAuthInfo()
    {

        if
        (
            Request::has(OAuth2Protocol::OAuth2Protocol_ClientAssertionType) &&
            Request::has(OAuth2Protocol::OAuth2Protocol_ClientAssertion)
        )
        {
            Log::debug
            (
                sprintf
                (
                    "ClientService::getCurrentClientAuthInfo params %s - %s present",
                    OAuth2Protocol::OAuth2Protocol_ClientAssertionType,
                    OAuth2Protocol::OAuth2Protocol_ClientAssertion
                )
            );

            return new ClientAssertionAuthenticationContext
            (
                Request::input(OAuth2Protocol::OAuth2Protocol_ClientAssertionType, ''),
                Request::input(OAuth2Protocol::OAuth2Protocol_ClientAssertion, '')
            );
        }


        if(Request::hasHeader('Authorization'))
        {

            Log::debug
            (
                "ClientService::getCurrentClientAuthInfo Authorization Header present"
            );

            $auth_header = Request::header('Authorization');
            $auth_header = trim($auth_header);
            $auth_header = explode(' ', $auth_header);

            if (!is_array($auth_header) || count($auth_header) < 2)
            {
                throw new MissingClientAuthorizationInfo('Wrong Authorization header format.');
            }

            $auth_header_content = $auth_header[1];
            $auth_header_content = base64_decode($auth_header_content);
            $auth_header_content = explode(':', $auth_header_content);

            if (!is_array($auth_header_content) || count($auth_header_content) !== 2)
            {
                throw new MissingClientAuthorizationInfo('Wrong Authorization header format.');
            }

            Log::debug
            (
                sprintf
                (
                    "ClientService::getCurrentClientAuthInfo client id %s - client secret %s",
                    $auth_header_content[0],
                    $auth_header_content[1]
                )
            );

            return new ClientCredentialsAuthenticationContext
            (
                urldecode($auth_header_content[0]),
                urldecode($auth_header_content[1]),
                OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic
            );
        }

        if(Request::has(OAuth2Protocol::OAuth2Protocol_ClientId))
        {
            Log::debug
            (
                sprintf
                (
                    "ClientService::getCurrentClientAuthInfo params %s - %s present",
                    OAuth2Protocol::OAuth2Protocol_ClientId,
                    OAuth2Protocol::OAuth2Protocol_ClientSecret
                )
            );

            $client_secret = null;
            $auth_type = OAuth2Protocol::TokenEndpoint_AuthMethod_None;

            if(Request::has(OAuth2Protocol::OAuth2Protocol_ClientSecret)){
                $client_secret =  urldecode(Request::input(OAuth2Protocol::OAuth2Protocol_ClientSecret, ''));
                $auth_type = OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretPost;
            }

            return new ClientCredentialsAuthenticationContext
            (
                urldecode(Request::input(OAuth2Protocol::OAuth2Protocol_ClientId, '')),
                $client_secret,
                $auth_type
            );
        }

        throw new InvalidClientAuthMethodException;
    }

    /**
     * @param array $payload
     * @return IEntity
     * @throws \Exception
     */
    public function create(array $payload):IEntity
    {

        return $this->tx_service->transaction(function () use ($payload) {

            $current_user  = $this->auth_service->getCurrentUser();

            $app_name =  trim($payload['app_name']);

            if($this->client_repository->getByApplicationName($app_name) != null){
                throw new ValidationException('there is already another application with that name, please choose another one.');
            }

            $client = ClientFactory::build($payload);
            $client = $this->client_credential_generator->generate($client);

            if(isset($payload['admin_users']) && is_array($payload['admin_users'])) {
                $admin_users = $payload['admin_users'];
                //add admin users
                foreach ($admin_users as $user_id) {
                    $user = $this->user_repository->getById(intval($user_id));
                    if (is_null($user)) throw new EntityNotFoundException(sprintf('user %s not found.', $user_id));
                    if(!$user instanceof User) continue;
                    $client->addAdminUser($user);
                }
            }

            $client->setOwner($current_user);

            $this->client_repository->add($client);

            return $client;
        });
    }


    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $id, array $payload):IEntity
    {

        return $this->tx_service->transaction(function () use ($id, $payload) {

            $editing_user = $this->auth_service->getCurrentUser();

            $client = $this->client_repository->getById($id);

            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException(sprintf('client id %s does not exists.', $id));
            }
            $app_name   = isset($payload['app_name']) ? trim($payload['app_name']) : null;
            if(!empty($app_name)) {
                $old_client = $this->client_repository->getByApplicationName($app_name);
                if(!is_null($old_client) && $old_client->getId() !== $client->getId())
                    throw new ValidationException('there is already another application with that name, please choose another one.');
            }
            $current_app_type = $client->getApplicationType();
            if($current_app_type !== $payload['application_type'])
            {
                throw new ValidationException('application type does not match.');
            }

            ClientFactory::populate($client, $payload);

            // validate uris
            switch($client->getApplicationType()) {
                case IClient::ApplicationType_Native: {

                    if (isset($payload['redirect_uris'])) {
                        $redirect_uris = explode(',', $payload['redirect_uris']);
                        //check that custom schema does not already exists for another registerd app
                        if (!empty($payload['redirect_uris'])) {
                            foreach ($redirect_uris as $uri) {
                                $uri = @parse_url($uri);
                                if (!isset($uri['scheme'])) {
                                    throw new ValidationException('invalid scheme on redirect uri.');
                                }
                                if (HttpUtils::isCustomSchema($uri['scheme'])) {
                                    if ($this->client_repository->hasCustomSchemeRegisteredForRedirectUrisOnAnotherClientThan($id, $uri['scheme'])) {
                                        throw new ValidationException(sprintf('schema %s:// already registered for another client.',
                                            $uri['scheme']));
                                    }
                                } else {
                                    if (!HttpUtils::isHttpSchema($uri['scheme'])) {
                                        throw new ValidationException(sprintf('scheme %s:// is invalid.',
                                            $uri['scheme']));
                                    }
                                }
                            }
                        }
                    }
                }
                break;
                case IClient::ApplicationType_Web_App:
                case IClient::ApplicationType_JS_Client: {
                    if (isset($payload['redirect_uris'])){
                        if (!empty($payload['redirect_uris'])) {
                            $redirect_uris = explode(',', $payload['redirect_uris']);
                            foreach ($redirect_uris as $uri) {
                                $uri = @parse_url($uri);
                                if (!isset($uri['scheme'])) {
                                    throw new ValidationException('invalid scheme on redirect uri.');
                                }
                                if (!HttpUtils::isHttpsSchema($uri['scheme'])) {
                                    throw new ValidationException(sprintf('scheme %s:// is invalid.', $uri['scheme']));
                                }
                            }
                        }
                    }
                    if($client->getApplicationType() === IClient::ApplicationType_JS_Client && isset($payload['allowed_origins']) &&!empty($payload['allowed_origins'])){
                        $allowed_origins = explode(',', $payload['allowed_origins']);
                        foreach ($allowed_origins as $uri) {
                            $uri = @parse_url($uri);
                            if (!isset($uri['scheme'])) {
                                throw new ValidationException('invalid scheme on allowed origin uri.');
                            }
                            if (!HttpUtils::isHttpsSchema($uri['scheme'])) {
                                throw new ValidationException(sprintf('scheme %s:// is invalid.', $uri['scheme']));
                            }
                        }
                    }
                }
                    break;
            }

            if(isset($payload['admin_users']) && is_array($payload['admin_users'])) {
                $admin_users = $payload['admin_users'];
                //add admin users
                $client->removeAllAdminUsers();
                foreach ($admin_users as $user_id) {
                    $user = $this->user_repository->getById(intval($user_id));
                    if (is_null($user)) throw new EntityNotFoundException(sprintf('user %s not found.', $user_id));
                    if(!$user instanceof User) continue;
                    $client->addAdminUser($user);
                }
            }

            $client->setEditedBy($editing_user);
            return $client;
        });
   }

    /**
     * @param int $id
     * @param int $scope_id
     * @return Client|null
     * @throws \Exception
     */
    public function addClientScope(int $id, int $scope_id):?Client
    {
        return $this->tx_service->transaction(function() use ($id, $scope_id){
            $client = $this->client_repository->getById($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException(sprintf("client id %s does not exists!", $id));
            }
            $owner = $client->getOwner();
            $scope = $this->scope_repository->getById($scope_id);
            if (is_null($scope) || !$scope instanceof ApiScope) {
                throw new EntityNotFoundException(sprintf("scope id %s does not exists!", $scope_id));
            }

            if($scope->isAssignedByGroups()) {
                if(!$owner->isGroupScopeAllowed($scope))
                    throw new ValidationException(sprintf('you cant assign to this client api scope %s', $scope_id));
            }

            if($scope->isSystem() && !$owner->canUseSystemScopes())
                throw new ValidationException(sprintf('you cant assign to this client api scope %s', $scope_id));

            $client->addScope($scope);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            return $client;
        });
    }

    /**
     * @param $id
     * @param $scope_id
     * @return IClient
     * @throws EntityNotFoundException
     */
    public function deleteClientScope(int $id, int $scope_id):?Client
    {
        return $this->tx_service->transaction(function() use ($id, $scope_id){
            $client = $this->client_repository->getById($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException(sprintf("Client id %s does not exists.", $id));
            }
            $scope = $this->scope_repository->getById($scope_id);
            if (is_null($scope) || !$scope instanceof ApiScope) {
                throw new EntityNotFoundException(sprintf("Scope id %s does not exists.", $scope_id));
            }
            if($scope->getName() == OAuth2Protocol::OpenIdConnect_Scope){
                throw new ValidationException(sprintf("Scope %s can not be removed.",  OAuth2Protocol::OpenIdConnect_Scope));
            }
            if($scope->getName() == OAuth2Protocol::OfflineAccess_Scope && $client->canRequestRefreshTokens()){
                throw new ValidationException(sprintf("Scope %s can not be removed.",  OAuth2Protocol::OfflineAccess_Scope));
            }
            $client->removeScope($scope);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            return $client;
        });

    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id):void
    {
         $this->tx_service->transaction(function () use ($id) {
            $client = $this->client_repository->getById($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException(sprintf("client id %s does not exists!", $id));
            }
            Event::dispatch('oauth2.client.delete', [$client->getClientId()]);
            $this->client_repository->delete($client);
        });
    }

    /**
     * @param int $id
     * @return Client|null
     * @throws \Exception
     */
    public function regenerateClientSecret(int $id):?Client
    {

        return $this->tx_service->transaction(function () use ($id)
        {
            $current_user = $this->auth_service->getCurrentUser();

            $client = $this->client_repository->getById($id);

            if (is_null($client) || !$client instanceof Client)
            {
                throw new EntityNotFoundException(sprintf("client id %d does not exists!.", $id));
            }

            if ($client->getClientType() != IClient::ClientType_Confidential)
            {
                throw new ValidationException
                (
                    sprintf
                    (
                        "client id %d is not confidential type!.",
                        $id
                    )
                );
            }

            $client = $this->client_credential_generator->generate($client, true);
            $client->setEditedBy($current_user);

            Event::dispatch('oauth2.client.regenerate.secret', array($client->getClientId()));

            return $client;
        });
    }

    /**
     * @param int $id
     * @return Client|null
     * @throws \Exception
     */
    public function lockClient(int $id):?Client
    {
        return $this->tx_service->transaction(function () use ($id) {

            $client = $this->client_repository->getById($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException($id, sprintf("client id %s does not exists!", $id));
            }
            $client->setLocked(true);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            Event::dispatch(new OAuth2ClientLocked($client->getClientId()));
            return $client;
        });

    }

    /**
     * @param int $id
     * @return bool
     * @throws EntityNotFoundException
     */
    public function unlockClient(int $id):?Client
    {
        return $this->tx_service->transaction(function () use ($id) {

            $client = $this->client_repository->getClientByIdentifier($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException($id, sprintf("client id %s does not exists!", $id));
            }
            $client->setLocked(false);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            return $client;
        });

    }

    /**
     * @param int $id
     * @param bool $active
     * @return Client|null
     * @throws \Exception
     */
    public function activateClient(int $id, bool $active):?Client
    {

        return $this->tx_service->transaction(function () use ($id, $active) {

            $client = $this->client_repository->getClientByIdentifier($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException($id, sprintf("client id %s does not exists!", $id));
            }
            $client->setActive($active);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            return $client;
        });
    }

    /**
     * @param int $id
     * @param bool $use_refresh_token
     * @return Client|null
     * @throws \Exception
     */
    public function setRefreshTokenUsage(int $id, bool $use_refresh_token):?Client
    {
        return $this->tx_service->transaction(function () use ($id, $use_refresh_token) {

            $client = $this->client_repository->getClientByIdentifier($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException($id, sprintf("client id %s does not exists!", $id));
            }
            $client->setUseRefreshToken($use_refresh_token);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            return $client;
        });
    }

    /**
     * @param int $id
     * @param bool $rotate_refresh_token
     * @return Client|null
     * @throws \Exception
     */
    public function setRotateRefreshTokenPolicy(int $id, bool $rotate_refresh_token):?Client
    {
        return $this->tx_service->transaction(function () use ($id, $rotate_refresh_token) {

            $client = $this->client_repository->getClientByIdentifier($id);
            if (is_null($client) || !$client instanceof Client) {
                throw new EntityNotFoundException($id, sprintf("client id %s does not exists!", $id));
            }

            $client->setRotateRefreshToken($rotate_refresh_token);
            $client->setEditedBy($this->auth_service->getCurrentUser());
            return $client;
        });
    }
}