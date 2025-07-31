<?php namespace App\Http\Controllers\Api\OAuth2;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\Http\Controllers\GetAllTrait;
use App\libs\Auth\Repositories\IGroupRepository;
use App\ModelSerializers\SerializerRegistry;
use OAuth2\IResourceServerContext;
use Utils\Services\ILogService;

/**
 * Class OAuth2GroupApiController
 * @package App\Http\Controllers\Api\OAuth2
 */
final class OAuth2GroupApiController extends OAuth2ProtectedController
{
    use GetAllTrait;

    /**
     * OAuth2UserApiController constructor.
     * @param IGroupRepository $repository
     * @param IResourceServerContext $resource_server_context
     * @param ILogService $log_service
     */
    public function __construct
    (
        IGroupRepository $repository,
        IResourceServerContext $resource_server_context,
        ILogService $log_service,
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->repository = $repository;
    }

    protected function getAllSerializerType(): string
    {
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'slug' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'slug' => 'sometimes|required|string',
        ];
    }

    public function getOrderRules(): array
    {
        return [
            'id',
            'name',
            'slug'
        ];
    }
}