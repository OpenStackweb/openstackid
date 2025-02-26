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

use Exception;
use OAuth2\Exceptions\InvalidApplicationType;
use OAuth2\Exceptions\InvalidGrantTypeException;
use OAuth2\Exceptions\InvalidOAuth2Request;
use OAuth2\Exceptions\UseRefreshTokenException;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Responses\OAuth2AccessTokenResponse;
use OAuth2\Responses\OAuth2IdTokenResponse;
use OAuth2\Services\ITokenService;
use OAuth2\OAuth2Protocol;
use OAuth2\Requests\OAuth2RefreshAccessTokenRequest;
use OAuth2\Requests\OAuth2Request;
use OAuth2\Requests\OAuth2TokenRequest;
use OAuth2\Responses\OAuth2Response;
use OAuth2\Services\IClientService;
use Utils\Services\ILogService;

/**
 * Class RefreshBearerTokenGrantType
 * @see http://tools.ietf.org/html/rfc6749#section-6
 * @package OAuth2\GrantTypes
 */
final class RefreshBearerTokenGrantType extends AbstractGrantType
{

    /**
     * RefreshBearerTokenGrantType constructor.
     * @param IClientService $client_service
     * @param IClientRepository $client_repository
     * @param ITokenService $token_service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IClientService $client_service,
        IClientRepository $client_repository,
        ITokenService $token_service,
        ILogService $log_service
    )
    {
        parent::__construct($client_service, $client_repository, $token_service, $log_service);
    }

    /**
     * @param OAuth2Request $request
     * @return bool
     */
    public function canHandle(OAuth2Request $request)
    {
        return
            $request instanceof OAuth2TokenRequest &&
            $request->isValid() &&
            $request->getGrantType() == $this->getType();
    }

    /** Not implemented , there is no first process phase on this grant type
     * @param OAuth2Request $request
     * @return mixed|void
     * @throws Exception
     */
    public function handle(OAuth2Request $request)
    {
        throw new InvalidOAuth2Request('not implemented!');
    }

    /**
     * Access Token issuance using a refresh token
     * The authorization server MUST:
     *  o  require client authentication for confidential clients or for any
     *     client that was issued client credentials (or with other
     *     authentication requirements),
     *  o  authenticate the client if client authentication is included and
     *     ensure that the refresh token was issued to the authenticated
     *     client, and
     * o  validate the refresh token.
     * @param OAuth2Request $request
     * @return OAuth2Response
     * @throws UseRefreshTokenException
     * @throws InvalidOAuth2Request
     * @throws InvalidApplicationType
     * @throws InvalidGrantTypeException
     */
    public function completeFlow(OAuth2Request $request)
    {

        if (!($request instanceof OAuth2RefreshAccessTokenRequest)) {
            throw new InvalidOAuth2Request;
        }

        parent::completeFlow($request);

        if (!$this->current_client->canRequestRefreshTokens()) {
            throw new InvalidApplicationType
            (
                sprintf
                (
                    'Client id %s client type must be %s or %s or support PKCE.',
                    $this->client_auth_context->getId(),
                    IClient::ApplicationType_Web_App,
                    IClient::ApplicationType_Native
                )
            );
        }

        if (!$this->current_client->useRefreshToken()) {
            throw new UseRefreshTokenException
            (
                sprintf
                (
                    "Current client id %s could not use refresh tokens.",
                    $this->client_auth_context->getId()
                )
            );
        }

        $refresh_token_value = $request->getRefreshToken();
        $scope = $request->getScope();
        $refresh_token = $this->token_service->getRefreshToken($refresh_token_value);

        if (is_null($refresh_token)) {
            throw new InvalidGrantTypeException
            (
                sprintf
                (
                    "Refresh token %s does not exists.",
                    $refresh_token_value
                )
            );
        }

        if ($refresh_token->getClientId() !== $this->current_client->getClientId()) {
            throw new InvalidGrantTypeException
            (
                sprintf
                (
                    "Refresh token %s does not belongs to client %s.",
                    $refresh_token_value, $this->current_client->getClientId()
                )
            );
        }

        $new_refresh_token = null;
        $access_token = $this->token_service->createAccessTokenFromRefreshToken($refresh_token, $scope);
        /*
         * the authorization server could employ refresh token
         * rotation in which a new refresh token is issued with every access
         * token refresh response.  The previous refresh token is invalidated
         * but retained by the authorization server.  If a refresh token is
         * compromised and subsequently used by both the attacker and the
         * legitimate client, one of them will present an invalidated refresh
         * token, which will inform the authorization server of the breach.
         */
        if ($this->current_client->useRotateRefreshTokenPolicy()) {
            $new_refresh_token = $this->token_service->createRefreshToken($access_token, true);
            $this->token_service->invalidateRefreshToken($refresh_token_value);
        }

        // add id token on response ( new id token )
        // see https://openid.net/specs/openid-connect-core-1_0.html#RefreshingAccessToken
        // considerations
        // 1. if the ID Token contains an auth_time Claim, its value MUST represent the time of the original
        //    authentication - not the time that the new ID token is issued.
        // 2. it SHOULD NOT have a nonce Claim, even when the ID Token issued at the time of the original authentication
        //   contained nonce; however, if it is present, its value MUST be the same as in the ID Token issued at the
        //  time of the original authentication
        $id_token = null;

        $access_token_scopes = explode(' ', $access_token->getScope());
        if(in_array(OAuth2Protocol::OpenIdConnect_Scope,$access_token_scopes)) {

            $id_token = $this->token_service->createIdToken
            (
                $this->current_client->getClientId(),
                $access_token
            );
        }

        if(!is_null($id_token))
            return new OAuth2IdTokenResponse
            (
                $access_token->getValue(),
                $access_token->getLifetime(),
                $id_token->toCompactSerialization(),
                !is_null($new_refresh_token) ? $new_refresh_token->getValue() : null,
                $scope
            );

        return new OAuth2AccessTokenResponse
        (
            $access_token->getValue(),
            $access_token->getLifetime(),
            !is_null($new_refresh_token) ? $new_refresh_token->getValue() : null,
            $scope
        );
    }

    /**
     * @return string
     */
    public function getType()
    {
        return OAuth2Protocol::OAuth2Protocol_GrantType_RefreshToken;
    }

    /**
     * @return array
     * @throws InvalidOAuth2Request
     */
    public function getResponseType()
    {
        throw new InvalidOAuth2Request('not implemented!');
    }

    /**
     * @param OAuth2Request $request
     * @return null|OAuth2Response
     */
    public function buildTokenRequest(OAuth2Request $request)
    {
        if ($request instanceof OAuth2TokenRequest) {
            if ($request->getGrantType() !== $this->getType()) {
                return null;
            }

            return new OAuth2RefreshAccessTokenRequest($request->getMessage());
        }

        return null;
    }
}