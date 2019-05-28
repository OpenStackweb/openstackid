<?php namespace OAuth2\Repositories;
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
use Models\OAuth2\AccessToken;
use models\utils\IBaseRepository;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Interface IAccessTokenRepository
 * @package OAuth2\Repositories
 */
interface IAccessTokenRepository extends IBaseRepository
{
    /**
     * @param int $client_identifier
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllByClientIdentifier(int $client_identifier, PagingInfo $paging_info):PagingResponse;

    /**
     * @param int $client_identifier
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllValidByClientIdentifier(int $client_identifier, PagingInfo $paging_info):PagingResponse;

    /**
     * @param int $user_id
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllByUserId(int $user_id, PagingInfo $paging_info):PagingResponse;


    /**
     * @param int $user_id
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllValidByUserId(int $user_id,PagingInfo $paging_info):PagingResponse;

    /**
     * @param string $hashed_value
     * @return AccessToken
     */
    function getByValue(string $hashed_value):?AccessToken;

    /**
     * @param string $hashed_value
     * @return AccessToken
     */
    function getByAuthCode(string $hashed_value):?AccessToken;

    /**
     * @param int $refresh_token_id
     * @return AccessToken[]
     */
    function getByRefreshToken(int $refresh_token_id):array;

}