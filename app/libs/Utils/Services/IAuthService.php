<?php namespace Utils\Services;
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

use Auth\User;
use OAuth2\Models\IClient;
use OpenId\Models\IOpenIdUser;
/**
 * Interface IAuthService
 */
interface IAuthService
{
    // authorization responses

    const AuthorizationResponse_None         = "None";
    const AuthorizationResponse_AllowOnce    = "AllowOnce";
    const AuthorizationResponse_AllowForever = "AllowForever";
    const AuthorizationResponse_DenyForever  = "DenyForever";
    const AuthorizationResponse_DenyOnce     = "DenyOnce";

    // authentication responses

    const AuthenticationResponse_None        = "None";
    const AuthenticationResponse_Cancel      = "Cancel";

    /**
     * @return bool
     */
    public function isUserLogged();

    /**
     * @return User|null
     */
    public function getCurrentUser():?User;

    /**
     * @param string $username
     * @param string $password
     * @param bool $remember_me
     * @return mixed
     */
    public function login($username, $password, $remember_me);

    /**
     * @param string $username
     * @return User|null
     */
    public function getUserByUsername(string $username):?User;

    /**
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id):?User;

    /**
     * @return string
     */
    public function getUserAuthorizationResponse();

    public function setUserAuthorizationResponse($auth_response);

    public function clearUserAuthorizationResponse();

    public function getUserAuthenticationResponse();

    public function setUserAuthenticationResponse($auth_response);

    public function clearUserAuthenticationResponse();

    /**
     * @return void
     */
    public function logout();

    /**
     * @param string $openid
     * @return User|null
     */
    public function getUserByOpenId(string $openid):?User;

    /**
     * @param string $user_id
     * @return string
     */
    public function unwrapUserId(string $user_id):string;

    /**
     * @param int $user_id
     * @param IClient $client
     * @return string
     */
    public function wrapUserId(int $user_id, IClient $client):string;


    /**
     * @return string
     */
    public function getSessionId():string;

    /**
     * @param $client_id
     * @return void
     */
    public function registerRPLogin(string $client_id);

    /**
     * @return string[]
     */
    public function getLoggedRPs():array;

    /**
     * @param string $jti
     * @return void
     */
    public function reloadSession(string $jti):void;

    const LOGGED_RELAYING_PARTIES_COOKIE_NAME = 'rps';

    /**
     * @param string $client_id
     * @param int $id_token_lifetime
     * @return string
     */
    public function generateJTI(string $client_id, int $id_token_lifetime):string;

    public function invalidateSession();

}