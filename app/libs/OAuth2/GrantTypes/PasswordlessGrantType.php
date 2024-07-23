<?php namespace OAuth2\GrantTypes;
/**
 * Copyright 2021 OpenStack Foundation
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

use Exception;
use Illuminate\Support\Facades\Auth;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Exceptions\InvalidApplicationType;
use OAuth2\Exceptions\InvalidClientCredentials;
use OAuth2\Exceptions\InvalidClientException;
use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\Exceptions\InvalidOTPException;
use OAuth2\Exceptions\InvalidRedeemOTPException;
use OAuth2\Exceptions\LockedClientException;
use OAuth2\Exceptions\OAuth2BaseException;
use OAuth2\Exceptions\ScopeNotAllowedException;
use OAuth2\Exceptions\UnAuthorizedClientException;
use OAuth2\Exceptions\UriNotAllowedException;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Requests\OAuth2AccessTokenRequestPasswordless;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Requests\OAuth2PasswordlessAuthenticationRequest;
use OAuth2\Requests\OAuth2Request;
use OAuth2\Requests\OAuth2TokenRequest;
use OAuth2\Responses\OAuth2AccessTokenResponse;
use OAuth2\Responses\OAuth2DirectErrorResponse;
use OAuth2\Responses\OAuth2IdTokenResponse;
use OAuth2\Responses\OAuth2PasswordlessAuthenticationResponse;
use OAuth2\Responses\OAuth2PasswordlessInlineAuthenticationResponse;
use OAuth2\Responses\OAuth2Response;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientJWKSetReader;
use OAuth2\Services\IClientService;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\ITokenService;
use OAuth2\Services\IUserConsentService;
use OAuth2\Strategies\ClientAuthContextValidatorFactory;
use OAuth2\Strategies\IOAuth2AuthenticationStrategy;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;

/**
 * Class PasswordlessGrantType
 * @package OAuth2\GrantTypes
 */
class PasswordlessGrantType extends InteractiveGrantType
{

    /**
     * @var Client
     */
    private $client = null;

    /**
     * PasswordlessGrantType constructor.
     * @param IApiScopeService $scope_service
     * @param IClientService $client_service
     * @param IClientRepository $client_repository
     * @param ITokenService $token_service
     * @param IAuthService $auth_service
     * @param IOAuth2AuthenticationStrategy $auth_strategy
     * @param ILogService $log_service
     * @param IUserConsentService $user_consent_service
     * @param IMementoOAuth2SerializerService $memento_service
     * @param ISecurityContextService $security_context_service
     * @param IPrincipalService $principal_service
     * @param IServerPrivateKeyRepository $server_private_key_repository
     * @param IClientJWKSetReader $jwk_set_reader_service
     */
    public function __construct
    (
        IApiScopeService $scope_service,
        IClientService $client_service,
        IClientRepository $client_repository,
        ITokenService $token_service,
        IAuthService $auth_service,
        IOAuth2AuthenticationStrategy $auth_strategy,
        ILogService $log_service,
        IUserConsentService $user_consent_service,
        IMementoOAuth2SerializerService $memento_service,
        ISecurityContextService $security_context_service,
        IPrincipalService $principal_service,
        IServerPrivateKeyRepository $server_private_key_repository,
        IClientJWKSetReader $jwk_set_reader_service
    )
    {

        parent::__construct
        (
            $client_service,
            $client_repository,
            $token_service,
            $log_service,
            $security_context_service,
            $principal_service,
            $auth_service,
            $user_consent_service,
            $scope_service,
            $auth_strategy,
            $memento_service,
            $server_private_key_repository,
            $jwk_set_reader_service
        );
    }

    /**
     * @param OAuth2Request $request
     * @return bool
     */
    public function canHandle(OAuth2Request $request)
    {
        // 2 steps flow
        // start flow
        if
        (
            $request instanceof OAuth2PasswordlessAuthenticationRequest &&
            OAuth2Protocol::responseTypeBelongsToFlow
            (
                $request->getResponseType(false),
                OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless
            )
        ) {
            return true;
        }
        // complete flow
        $request = $this->buildTokenRequest($request);
        if
        (
            !is_null($request) &&
            $request instanceof OAuth2AccessTokenRequestPasswordless &&
            $request->getGrantType() == $this->getType()
        ) {
            return true;
        }

        return false;
    }

    public function getType()
    {
        return OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless;
    }

    public function getResponseType()
    {
        return OAuth2Protocol::getValidResponseTypes(OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless);
    }

    protected function checkClientTypeAccess(IClient $client)
    {
        if (!$client->isPasswordlessEnabled()) {
            throw new InvalidApplicationType
            (
                sprintf
                (
                    "client id %s must have Passwordless enabled",
                    $client->getClientId(),
                )
            );
        }
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @param bool $has_former_consent
     * @return OAuth2PasswordlessAuthenticationResponse|OAuth2Response
     * @throws InvalidOAuth2Request
     */
    protected function buildResponse(OAuth2AuthorizationRequest $request, $has_former_consent)
    {
        if (!($request instanceof OAuth2PasswordlessAuthenticationRequest)) {
            throw new InvalidOAuth2Request;
        }

        $otp = $this->token_service->createOTPFromRequest($request, $this->client);

        if ($otp->getConnection() === OAuth2Protocol::OAuth2PasswordlessConnectionInline)
            return new OAuth2PasswordlessInlineAuthenticationResponse(
                $otp->getValue(),
                $otp->getLength(),
                $otp->getRemainingLifetime(),
                $otp->getScope()
            );

        return new OAuth2PasswordlessAuthenticationResponse
        (
            $otp->getLength(),
            $otp->getRemainingLifetime(),
            $otp->getScope()
        );
    }

    /**
     * @param OAuth2Request $request
     * @return OAuth2Response
     * @throws \Exception
     */
    public function handle(OAuth2Request $request)
    {
        try {

            if (!($request instanceof OAuth2PasswordlessAuthenticationRequest)) {
                throw new InvalidOAuth2Request;
            }

            if(!$request->isValid()){
                throw new InvalidOAuth2Request($request->getLastValidationError());
            }

            $client_id = $request->getClientId();
            $this->client = $this->client_repository->getClientById($client_id);

            if (is_null($this->client)) {
                throw new InvalidClientException
                (
                    sprintf
                    (
                        "client_id %s does not exists!",
                        $client_id
                    )
                );
            }

            if (!$this->client->isActive() || $this->client->isLocked()) {
                throw new LockedClientException
                (
                    sprintf
                    (
                        'client id %s is locked',
                        $client_id
                    )
                );
            }

            $this->checkClientTypeAccess($this->client);

            //check redirect uri
            $redirect_uri = $request->getRedirectUri();

            if (!empty($redirect_uri) && !$this->client->isUriAllowed($redirect_uri)) {
                throw new UriNotAllowedException
                (
                    $redirect_uri
                );
            }

            //check requested scope
            $scope = $request->getScope();
            $this->log_service->debug_msg(sprintf("scope %s", $scope));
            if (empty($scope) || !$this->client->isScopeAllowed($scope)) {
                throw new ScopeNotAllowedException($scope);
            }

            if ($request->getConnection() === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
                // we need to send out the credentials or a resource server
                // get client credentials from request..
                $this->client_auth_context = $this->client_service->getCurrentClientAuthInfo();
                // retrieve client from storage ...
                $impersonating_client = $this->client_repository->getClientByIdCacheable($this->client_auth_context->getId());
                if (is_null($impersonating_client)) {
                    throw new InvalidClientException("Invalid impersonating client for inline OTP request");
                }

                if (!$impersonating_client->isActive() || $impersonating_client->isLocked()) {
                    throw new LockedClientException
                    (
                        sprintf
                        (
                            'Client id %s is locked.',
                            $this->client_auth_context->getId()
                        )
                    );
                }

                if (!$impersonating_client->isResourceServerClient()) {
                    throw new InvalidClientException("Invalid impersonating client for inline OTP request");
                }

                $resource_server = $impersonating_client->getResourceServer();

                if (!$resource_server->canImpersonateClient($this->client)) {
                    throw new InvalidClientException("Invalid impersonating client for inline OTP request");
                }

                $this->client_auth_context->setClient($impersonating_client);

                if (!ClientAuthContextValidatorFactory::build($this->client_auth_context)->validate($this->client_auth_context))
                    throw new InvalidClientCredentials
                    (
                        sprintf
                        (
                            'Invalid credentials for client id %s.',
                            $this->client_auth_context->getId()
                        )
                    );
            }

            $response = $this->buildResponse($request, false);
            // clear save data ...
            $this->auth_service->clearUserAuthorizationResponse();
            $this->memento_service->forget();
            return $response;

        }
        catch(OAuth2BaseException $ex){
            $this->log_service->warning($ex);
            // clear save data ...
            $this->auth_service->clearUserAuthorizationResponse();
            $this->memento_service->forget();
            return new OAuth2DirectErrorResponse($ex->getError(), $ex->getMessage());
        }
        catch (Exception $ex) {
            $this->log_service->error($ex);
            // clear save data ...
            $this->auth_service->clearUserAuthorizationResponse();
            $this->memento_service->forget();
            throw $ex;
        }
    }

    /**
     * Implements last request processing for Authorization code (Access Token Request processing)
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.3 and
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.4
     * @param OAuth2Request $request
     * @return OAuth2AccessTokenResponse
     * @throws \Exception
     * @throws InvalidClientException
     * @throws UnAuthorizedClientException
     * @throws UriNotAllowedException
     */
    public function completeFlow(OAuth2Request $request)
    {
        try {

            if (!($request instanceof OAuth2AccessTokenRequestPasswordless)) {
                throw new InvalidOAuth2Request;
            }

            if (!$request->isValid()) {
                throw new InvalidOAuth2Request($request->getLastValidationError());
            }

            if ($request->getConnection() === OAuth2Protocol::OAuth2PasswordlessConnectionInline) {
                throw new InvalidOAuth2Request(sprintf("OTP connection value (%s) not valid on this flow.", OAuth2Protocol::OAuth2PasswordlessConnectionInline));
            }

            parent::completeFlow($request);

            $this->client = $this->client_auth_context->getClient();
            $this->checkClientTypeAccess($this->client);
            $otp = OAuth2OTP::fromRequest($request, $this->client->getOtpLength());

            $access_token = $this->token_service->createAccessTokenFromOTP
            (
                $otp,
                $this->client
            );

            $this->principal_service->register
            (
                $otp->getUserId(),
                $otp->getAuthTime()
            );

            $id_token = $this->token_service->createIdToken
            (
                $this->client->getClientId(),
                $access_token,
                $otp->getNonce()
            );

            $refresh_token = $access_token->getRefreshToken();

            if (!is_null($access_token))
                $refresh_token = $access_token->getRefreshToken();

            $response = new OAuth2IdTokenResponse
            (
                is_null($access_token) ? null  : $access_token->getValue(),
                is_null($access_token) ? null  : $access_token->getLifetime(),
                is_null($id_token) ? null      : $id_token->toCompactSerialization(),
                is_null($refresh_token) ? null : $refresh_token->getValue()
            );

            $user = $this->auth_service->getUserByUsername($otp->getUserName());

            // emmit login
            Auth::login($user, false);

            $this->security_context_service->clear();

            return $response;
        } catch (InvalidOTPException $ex) {
            $this->log_service->warning($ex);
            $this->security_context_service->clear();
            throw new InvalidRedeemOTPException
            (
                $ex->getMessage()
            );
        }
    }


    /**
     * @param OAuth2Request $request
     * @return OAuth2AccessTokenRequestPasswordless|OAuth2Response|null
     */
    public function buildTokenRequest(OAuth2Request $request)
    {
        if ($request instanceof OAuth2TokenRequest)
        {
            if ($request->getGrantType() !== $this->getType())
            {
                return null;
            }
            return new OAuth2AccessTokenRequestPasswordless($request->getMessage());
        }
        return null;
    }

}