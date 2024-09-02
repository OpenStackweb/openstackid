<?php namespace OAuth2\Services;
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

use Auth\User;
use jwt\IBasicJWT;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\Exceptions\InvalidAuthorizationCodeException;
use OAuth2\Exceptions\ReplayAttackException;
use OAuth2\Models\AuthorizationCode;
use OAuth2\Models\AccessToken;
use OAuth2\Models\RefreshToken;
use OAuth2\Exceptions\InvalidAccessTokenException;
use OAuth2\Exceptions\InvalidGrantTypeException;
use OAuth2\Requests\OAuth2AuthorizationRequest;
use OAuth2\Requests\OAuth2PasswordlessAuthenticationRequest;
use Utils\Model\AbstractIdentifier;

/**
 * Interface ITokenService
 * Defines the interface for an OAuth2 Token Service
 * Provides all Tokens related operations (create, get and revoke)
 * @package OAuth2\Services
 */
interface ITokenService {

    /**
     * Creates a brand new authorization code
     * @param OAuth2AuthorizationRequest $request
     * @param bool $has_previous_user_consent
     * @return AbstractIdentifier
     */
    public function createAuthorizationCode
    (
        OAuth2AuthorizationRequest $request,
        bool $has_previous_user_consent = false
    ):AbstractIdentifier;


    /**
     * Retrieves a given Authorization Code
     * @param $value
     * @return AuthorizationCode
     * @throws ReplayAttackException
     * @throws InvalidAuthorizationCodeException
     */
    public function getAuthorizationCode($value);

    /** Given an Authorization code, creates a brand new Access Token
     * @param $auth_code AuthorizationCode
     * @param null $redirect_uri
     * @return AccessToken
     */
    public function createAccessToken(AuthorizationCode $auth_code, $redirect_uri=null);

    /**
     * Create a brand new Access Token by params
     * @param $client_id
     * @param $scope
     * @param $audience
     * @param null $user_id
     * @return AccessToken
     */
    public function createAccessTokenFromParams($client_id,$scope, $audience,$user_id=null);


    /** Creates a new Access Token from a given refresh token, and invalidate former associated
     *  Access Token
     * @param RefreshToken $refresh_token
     * @param null $scope
     * @return mixed
     */
    public function createAccessTokenFromRefreshToken(RefreshToken $refresh_token, $scope=null);

    /**
     * Retrieves a given Access Token
     * @param $value
     * @param $is_hashed
     * @return AccessToken
     * @throws InvalidAccessTokenException
     * @throws InvalidGrantTypeException
     */
    public function getAccessToken($value, $is_hashed = false);

    /**
     * @param AuthorizationCode $auth_code
     * @return AccessToken|null
     */
    public function getAccessTokenByAuthCode(AuthorizationCode $auth_code);

    /**
     * Checks if current_ip has access rights on the given $access_token
     * @param AccessToken $access_token
     * @param $current_ip
     * @return bool
     */
    public function checkAccessTokenAudience(AccessToken $access_token, $current_ip);

    /**
     * Creates a new refresh token and associate it with given access token
     * @param AccessToken $access_token
     * @param boolean $refresh_cache
     * @return RefreshToken
     */
    public function createRefreshToken(AccessToken &$access_token, $refresh_cache = false);

    /**
     * Get a refresh token by its value
     * @param string $value
     * @param bool $is_hashed
     * @return RefreshToken
     * @throws ReplayAttackException
     * @throws InvalidGrantTypeException
     */
    public function getRefreshToken($value, $is_hashed = false);

    /**
     * Revokes all related tokens to a specific auth code
     * @param $auth_code Authorization Code
     * @return mixed
     */
    public function revokeAuthCodeRelatedTokens($auth_code);

    /**
     * Revokes all related tokens to a specific client id
     * @param $client_id
     */
    public function revokeClientRelatedTokens($client_id);

    /**
     * Revokes a given access token
     * @param $value
     * @param bool $is_hashed
     * @param User $current_user
     * @return bool
     */
    public function revokeAccessToken($value, $is_hashed = false, ?User $current_user = null);

    /**
     * @param $value
     * @param bool|false $is_hashed
     * @return bool
     */
    public function expireAccessToken($value, $is_hashed = false);

    /**
     * @param $value refresh_token value
     * @param bool $is_hashed
     * @return bool
     */
    public function clearAccessTokensForRefreshToken($value, $is_hashed = false);

    /**
     * Mark a given refresh token as void
     * @param string $value
     * @param bool $is_hashed
     * @param User $current_user
     * @return bool
     */
    public function invalidateRefreshToken(string $value, bool $is_hashed = false, ?User $current_user = null);

    /**
     * Revokes a give refresh token and all related access tokens
     * @param string $value
     * @param bool $is_hashed
     * @param User $current_user
     * @return mixed
     */
    public function revokeRefreshToken(string $value, bool $is_hashed = false, ?User $current_user = null);

    /**
     * @param string $client_id
     * @param AccessToken|null $access_token
     * @param AuthorizationCode|null $auth_code
     * @param string|null $nonce
     * @return IBasicJWT
     */
    public function createIdToken
    (
        string $client_id,
        ?AccessToken $access_token = null,
        ?string $nonce = null,
        ?AuthorizationCode $auth_code = null
    ):IBasicJWT;

    /**
     * @param OAuth2PasswordlessAuthenticationRequest $request
     * @param Client|null $client
     * @return OAuth2OTP
     * @throws \Exception
     */
    public function createOTPFromRequest(OAuth2PasswordlessAuthenticationRequest $request, ?Client $client):OAuth2OTP;

    /**
     * @param array $payload
     * @param Client|null $client
     * @return OAuth2OTP
     * @throws \Exception
     */
    public function createOTPFromPayload(array $payload, ?Client $client):OAuth2OTP;

    /**
     * @param OAuth2OTP $otp
     * @param Client|null $client
     * @return AccessToken
     */
    public function createAccessTokenFromOTP
    (
       OAuth2OTP &$otp,
        ?Client $client
    ):AccessToken;

    /**
     * @param User $user
     * @return void
     */
    public function revokeUsersToken(User $user):void;
}