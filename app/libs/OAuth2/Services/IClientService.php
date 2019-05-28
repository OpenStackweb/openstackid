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
use App\Services\IBaseService;
use models\exceptions\EntityNotFoundException;
use Models\OAuth2\Client;
use OAuth2\Exceptions\InvalidClientAuthMethodException;
use OAuth2\Exceptions\MissingClientAuthorizationInfo;
use OAuth2\Models\ClientAuthenticationContext;
use models\exceptions\ValidationException;
/**
 * Interface IClientService
 * @package OAuth2\Services
 */
interface IClientService  extends IBaseService {

    /**
     * Clients in possession of a client password MAY use the HTTP Basic
     * authentication scheme as defined in [RFC2617] to authenticate with
     * the authorization server
     * Alternatively, the authorization server MAY support including the
     * client credentials in the request-body using the following
     * parameters:
     * implementation of @link http://tools.ietf.org/html/rfc6749#section-2.3.1
     * implementation of @link http://openid.net/specs/openid-connect-core-1_0.html#ClientAuthentication
     * @throws InvalidClientAuthMethodException
     * @throws MissingClientAuthorizationInfo
     * @return ClientAuthenticationContext
     */
    public function getCurrentClientAuthInfo();

    /**
     * @param int $id
     * @param int $scope_id
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addClientScope(int $id, int $scope_id):?Client;

    /**
     * @param int $id
     * @param int $scope_id
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteClientScope(int $id, int $scope_id):?Client;

    /**
     * Regenerates Client Secret
     * @param int $id
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function regenerateClientSecret(int $id):?Client;

    /**
     * Lock a client application by id
     * @param int $id
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function lockClient(int $id):?Client;

    /**
     * unLock a client application by id
     * @param int $id
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function unlockClient(int $id):?Client;

    /**
     * Activate/Deactivate given client
     * @param int $id
     * @param bool $active
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function activateClient(int $id, bool $active):?Client;

    /**
     * set/unset refresh token usage for a given client
     * @param int $id
     * @param bool $use_refresh_token
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function setRefreshTokenUsage(int $id, bool $use_refresh_token):?Client;

    /**
     * set/unset rotate refresh token policy for a given client
     * @param int $id
     * @param bool $rotate_refresh_token
     * @return Client|null
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function setRotateRefreshTokenPolicy(int $id, bool $rotate_refresh_token):?Client;

} 