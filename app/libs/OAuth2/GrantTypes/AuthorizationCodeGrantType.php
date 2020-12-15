<?php namespace OAuth2\GrantTypes;
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

use App\libs\Utils\URLUtils;
use Exception;
use Illuminate\Support\Facades\Log;
use Models\OAuth2\Client;
use OAuth2\Exceptions\ExpiredAuthorizationCodeException;
use OAuth2\Exceptions\InvalidApplicationType;
use OAuth2\Exceptions\InvalidAuthorizationCodeException;
use OAuth2\Exceptions\InvalidClientException;
use OAuth2\Exceptions\InvalidClientType;
use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\Exceptions\InvalidRedeemAuthCodeException;
use OAuth2\Exceptions\OAuth2GenericException;
use OAuth2\Exceptions\UnAuthorizedClientException;
use OAuth2\Exceptions\UriNotAllowedException;
use OAuth2\Factories\OAuth2AccessTokenResponseFactory;
use OAuth2\Factories\OAuth2PKCEValidationMethodFactory;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\ITokenService;
use OAuth2\OAuth2Protocol;
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Requests\OAuth2AccessTokenRequestAuthCode;
use OAuth2\Requests\OAuth2AuthenticationRequest;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Requests\OAuth2Request;
use OAuth2\Requests\OAuth2TokenRequest;
use OAuth2\Responses\OAuth2AccessTokenResponse;
use OAuth2\Responses\OAuth2AuthorizationResponse;
use OAuth2\Services\IApiScopeService;
use OAuth2\Services\IClientJWKSetReader;
use OAuth2\Services\IClientService;
use OAuth2\Services\IMementoOAuth2SerializerService;
use OAuth2\Services\IPrincipalService;
use OAuth2\Services\ISecurityContextService;
use OAuth2\Services\IUserConsentService;
use OAuth2\Strategies\IOAuth2AuthenticationStrategy;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;
/**
 * Class AuthorizationCodeGrantType
 * Authorization Code Grant Implementation
 * The authorization code grant type is used to obtain both access
 * tokens and refresh tokens and is optimized for confidential clients.
 * Since this is a redirection-based flow, the client must be capable of
 * interacting with the resource owner's user-agent (typically a web
 * browser) and capable of receiving incoming requests (via redirection)
 * from the authorization server.
 * @see http://tools.ietf.org/html/rfc6749#section-4.1
 * @package OAuth2\GrantTypes
 */
class AuthorizationCodeGrantType extends InteractiveGrantType
{

    /**
     * AuthorizationCodeGrantType constructor.
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
        IApiScopeService                $scope_service,
        IClientService                  $client_service,
        IClientRepository               $client_repository,
        ITokenService                   $token_service,
        IAuthService                    $auth_service,
        IOAuth2AuthenticationStrategy   $auth_strategy,
        ILogService                     $log_service,
        IUserConsentService             $user_consent_service,
        IMementoOAuth2SerializerService $memento_service,
        ISecurityContextService         $security_context_service,
        IPrincipalService               $principal_service,
        IServerPrivateKeyRepository     $server_private_key_repository,
        IClientJWKSetReader             $jwk_set_reader_service
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
        if
        (
            $request instanceof OAuth2AuthorizationRequest &&
            $request->isValid() &&
            OAuth2Protocol::responseTypeBelongsToFlow
            (
                $request->getResponseType(false),
                OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode
            )
        )
        {
            return true;
        }
        if
        (
            $request instanceof OAuth2TokenRequest &&
            $request->isValid() &&
            $request->getGrantType() == $this->getType()
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @return mixed|string
     */
    public function getType()
    {
        return OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode;
    }

    /**
     * @return array
     */
    public function getResponseType()
    {
        return OAuth2Protocol::getValidResponseTypes(OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode);
    }

    /**
     * Implements last request processing for Authorization code (Access Token Request processing)
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.3 and
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.4
     * @param OAuth2Request $request
     * @return OAuth2AccessTokenResponse
     * @throws InvalidAuthorizationCodeException
     * @throws ExpiredAuthorizationCodeException
     * @throws Exception
     * @throws InvalidClientException
     * @throws UnAuthorizedClientException
     * @throws UriNotAllowedException
     */
    public function completeFlow(OAuth2Request $request)
    {

        if (!($request instanceof OAuth2AccessTokenRequestAuthCode))
        {
            throw new InvalidOAuth2Request;
        }

        try
        {
            parent::completeFlow($request);
            $client = $this->client_auth_context->getClient();
            $this->checkClientTypeAccess($client);

            $current_redirect_uri = $request->getRedirectUri();
            //verify redirect uri
            if (empty($current_redirect_uri) || !$this->current_client->isUriAllowed($current_redirect_uri))
            {
                throw new UriNotAllowedException
                (
                    empty($current_redirect_uri)? "missing" : $current_redirect_uri
                );
            }

            $code = $request->getCode();
            // verify that the authorization code is valid
            // The client MUST NOT use the authorization code
            // more than once.  If an authorization code is used more than
            // once, the authorization server MUST deny the request and SHOULD
            // revoke (when possible) all tokens previously issued based on
            // that authorization code.  The authorization code is bound to
            // the client identifier and redirection URI.
            $auth_code = $this->token_service->getAuthorizationCode($code);

            // reload session state
            $client_id = $auth_code->getClientId();

            $this->security_context_service->save
            (
                $this->security_context_service->get()
                    ->setAuthTimeRequired
                    (
                        $auth_code->isAuthTimeRequested()
                    )
                    ->setRequestedUserId
                    (
                        $auth_code->getUserId()
                    )
            );

            //ensure that the authorization code was issued to the authenticated
            //confidential client, or if the client is public, ensure that the
            //code was issued to "client_id" in the request
            if ($client_id != $this->client_auth_context->getId())
            {
                throw new InvalidRedeemAuthCodeException
                (
                    sprintf
                    (
                        "auth code was issued for another client id!."
                    )
                );
            }

            // ensure that the "redirect_uri" parameter is present if the
            // "redirect_uri" parameter was included in the initial authorization
            // and if included ensure that their values are identical.
            $redirect_uri = $auth_code->getRedirectUri();
            Log::debug(sprintf("AuthorizationCodeGrantType::completeFlow auth code redirect uri %s current_redirect_uri %s", $redirect_uri, $current_redirect_uri));
            if (!empty($redirect_uri) && URLUtils::normalizeUrl($redirect_uri) !== URLUtils::normalizeUrl($current_redirect_uri))
            {
                throw new UriNotAllowedException($current_redirect_uri);
            }

            if($client->isPKCEEnabled()){
                /**
                 * PKCE Validation
                 * @see https://tools.ietf.org/html/rfc7636#page-10
                 * @see https://oauth.net/2/pkce
                 * server Verifies code_verifier before Returning the Tokens
                 *  If the "code_challenge_method" from Section 4.3 was "S256", the
                 * received "code_verifier" is hashed by SHA-256, base64url-encoded, and
                 * then compared to the "code_challenge", i.e.:
                 * BASE64URL-ENCODE(SHA256(ASCII(code_verifier))) == code_challenge
                 * If the "code_challenge_method" from Section 4.3 was "plain", they are
                 * compared directly, i.e.:
                 * code_verifier == code_challenge.
                 * If the values are equal, the token endpoint MUST continue processing
                 * as normal (as defined by OAuth 2.0
                 */

                if(!$request instanceof OAuth2AccessTokenRequestAuthCode)
                    throw new InvalidOAuth2Request();

                $strategy = OAuth2PKCEValidationMethodFactory::build($auth_code, $request);

                if(!$strategy->isValid()){
                    throw new InvalidOAuth2Request("PKCE request can not be validated");
                }
            }

            $this->principal_service->register
            (
                $auth_code->getUserId(),
                $auth_code->getAuthTime()
            );

            $response = OAuth2AccessTokenResponseFactory::build
            (
                $this->token_service,
                $auth_code,
                $request
            );

            $this->security_context_service->clear();

            return $response;
        }
        catch (InvalidAuthorizationCodeException $ex)
        {
            $this->log_service->error($ex);
            $this->security_context_service->clear();
            throw new InvalidRedeemAuthCodeException
            (
                $ex->getMessage()
            );
        }
    }

    /**
     * @param OAuth2Request $request
     * @return mixed|null|OAuth2AccessTokenRequestAuthCode
     */
    public function buildTokenRequest(OAuth2Request $request)
    {
        if ($request instanceof OAuth2TokenRequest)
        {
            if ($request->getGrantType() !== $this->getType())
            {
                return null;
            }
            return new OAuth2AccessTokenRequestAuthCode($request->getMessage());
        }
        return null;
    }

    /**
     * @param IClient $client
     * @throws InvalidApplicationType
     * @throws InvalidClientType
     * @return void
     */
    protected function checkClientTypeAccess(IClient $client)
    {
        if
        (
           !(
               $client->getClientType()      === IClient::ClientType_Confidential ||
               $client->getApplicationType() === IClient::ApplicationType_Native ||
               $client->isPKCEEnabled()
           )
        )
        {
            throw new InvalidApplicationType
            (
                sprintf
                (
                    "client id %s - Application type must be %s or %s or have PKCE enabled",
                    $client->getClientId(),
                    IClient::ClientType_Confidential,
                    IClient::ApplicationType_Native
                )
            );
        }
    }

    /**
     * @param OAuth2AuthorizationRequest $request
     * @param bool $has_former_consent
     * @return OAuth2AuthorizationResponse
     * @throws OAuth2GenericException
     */
    protected function buildResponse(OAuth2AuthorizationRequest $request, $has_former_consent)
    {
        $auth_code = $this->token_service->createAuthorizationCode
        (
            $request,
            $has_former_consent
        );

        if (is_null($auth_code))
        {
            throw new OAuth2GenericException("Invalid Auth Code");
        }

        // http://openid.net/specs/openid-connect-session-1_0.html#CreatingUpdatingSessions
        $session_state = $this->getSessionState
        (
            self::getOrigin
            (
                $request->getRedirectUri()
            ),
            $request->getClientId(),

            $this->principal_service->get()->getOPBrowserState()
        );

        return new OAuth2AuthorizationResponse
        (
            $request->getRedirectUri(),
            $auth_code->getValue(),
            $request->getScope(),
            $request->getState(),
            $session_state
        );
    }
}