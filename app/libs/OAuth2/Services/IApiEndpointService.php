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
use models\exceptions\ValidationException;
use Models\OAuth2\ApiEndpoint;
/**
 * Interface IApiEndpointService
 * @package OAuth2\Services
 */
interface IApiEndpointService extends IBaseService {

    /**
     * Adds a new required scope to a given api endpoint,
     * given scope must belongs to owner api of the given endpoint
     * @param int $api_endpoint_id
     * @param int $scope_id
     * @return ApiEndpoint
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addRequiredScope(int $api_endpoint_id, int $scope_id):ApiEndpoint;

    /**
     * Remove a required scope to a given api endpoint,
     * given scope must belongs to owner api of the given endpoint
     * @param int $api_endpoint_id
     * @param int $scope_id
     * @return ApiEndpoint
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeRequiredScope(int $api_endpoint_id, int $scope_id):ApiEndpoint;

} 