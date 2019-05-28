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
use Models\OAuth2\Api;
use Models\OAuth2\ApiEndpoint;
use models\utils\IBaseRepository;
/**
 * Interface IApiEndpointRepository
 * @package OAuth2\Repositories
 */
interface IApiEndpointRepository extends IBaseRepository
{
    /**
     * @param string $url
     * @param string $http_method
     * @return ApiEndpoint
     */
    public function getApiEndpointByUrlAndMethod(string $url, string $http_method):?ApiEndpoint;

    /**
     * @param string $url
     * @param string $http_method
     * @param Api $api
     * @return ApiEndpoint
     */
    public function getApiEndpointByUrlAndMethodAndApi(string $url, string $http_method, Api $api):?ApiEndpoint;

    /**
     * @param string $url
     * @return ApiEndpoint
     */
    public function getApiEndpointByUrl(string $url):?ApiEndpoint;

}