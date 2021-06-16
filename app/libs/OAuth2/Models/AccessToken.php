<?php namespace OAuth2\Models;
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
use Models\OAuth2\OAuth2OTP;
use OAuth2\OAuth2Protocol;
use Zend\Math\Rand;
/**
 * Class AccessToken
 * @see http://tools.ietf.org/html/rfc6749#section-1.4
 * @package OAuth2\Models
 */
class AccessToken extends Token {

    /**
     * @var AuthorizationCode
     */
    private $auth_code;

    /**
     * @var OAuth2OTP
     */
    private $otp;

    /**
     * @var RefreshToken
     */
    private $refresh_token;

    const Length = 128;

    public function __construct()
    {
        parent::__construct(self::Length);
    }

    /**
     * @param AuthorizationCode $auth_code
     * @param int $lifetime
     * @return AccessToken
     */
    public static function create(AuthorizationCode $auth_code, $lifetime = 3600){
        $instance               = new self();
        $instance->user_id      = $auth_code->getUserId();
        $instance->scope        = $auth_code->getScope();
        // client id (oauth2) not client identifier
        $instance->client_id    = $auth_code->getClientId();
        $instance->auth_code    = $auth_code->getValue();
        $instance->audience     = $auth_code->getAudience();
        $instance->lifetime     = intval($lifetime);
        $instance->is_hashed    = false;
        return $instance;
    }

    /**
     * @param OAuth2OTP $otp
     * @param string $client_id
     * @param string $audience
     * @param int $lifetime
     * @return AccessToken
     */
    public static function createFromOTP(OAuth2OTP $otp,string $client_id, string $audience, $lifetime = 3600){
        $instance               = new self();
        $instance->otp          = $otp;
        $instance->scope        = $otp->getScope();
        // client id (oauth2) not client identifier
        $instance->client_id    = $client_id;
        $instance->audience     = $audience;
        $instance->lifetime     = intval($lifetime);
        $instance->is_hashed    = false;
        return $instance;
    }

    public static function createFromParams($scope, $client_id, $audience,$user_id,$lifetime){
        $instance = new self();
        $instance->scope         = $scope;
        $instance->client_id     = $client_id;
        $instance->user_id       = $user_id;
        $instance->auth_code     = null;
        $instance->audience      = $audience;
        $instance->refresh_token = null;
        $instance->lifetime      = intval($lifetime);
        $instance->is_hashed     = false;
        return $instance;
    }

    public static function createFromRefreshToken(RefreshToken $refresh_token,$scope = null,  $lifetime = 3600){
        $instance = new self();
        $instance->scope         = $scope;
        $instance->from_ip       = $refresh_token->getFromIp();
        $instance->user_id       = $refresh_token->getUserId();
        $instance->client_id     = $refresh_token->getClientId();
        $instance->auth_code     = null;
        $instance->refresh_token = $refresh_token;
        $instance->audience      = $refresh_token->getAudience();
        $instance->lifetime      = intval($lifetime);
        $instance->is_hashed    =  false;
        return $instance;
    }

    public static function load($value, AuthorizationCode $auth_code, $issued = null, $lifetime = 3600, $is_hashed=false){
        $instance = new self();
        $instance->value        = $value;
        $instance->scope        = $auth_code->getScope();
        $instance->client_id    = $auth_code->getClientId();
        $instance->user_id      = $auth_code->getUserId();
        $instance->auth_code    = $auth_code->getValue();
        $instance->audience     = $auth_code->getAudience();
        $instance->from_ip      = $auth_code->getFromIp();
        $instance->issued       = $issued;
        $instance->lifetime     = intval($lifetime);
        $instance->is_hashed    = $is_hashed;
        return $instance;
    }

    /**
     * @return AuthorizationCode
     */
    public function getAuthCode(){
        return $this->auth_code;
    }

    /**
     * @return RefreshToken
     */
    public function getRefreshToken(){
        return $this->refresh_token;
    }

    /**
     * @param RefreshToken $refresh_token
     * @return $this
     */
    public function setRefreshToken(RefreshToken $refresh_token){
        $this->refresh_token = $refresh_token;
        return $this;
    }


    public function toJSON(){
        return '{}';
    }

    public function fromJSON($json){

    }

    /**
     * @return string
     */
    public function getType():string
    {
        return 'access_token';
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
       return [];
    }

    /**
     * @return OAuth2OTP
     */
    public function getOtp(): ?OAuth2OTP
    {
        return $this->otp;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        if(!is_null($this->otp)){
            $this->user_id = $this->otp->getUserId();
        }
        return intval($this->user_id);
    }

    public function generateValue(): string
    {
        $this->value = Rand::getString($this->len, OAuth2Protocol::VsChar);
        return $this->value;
    }
}