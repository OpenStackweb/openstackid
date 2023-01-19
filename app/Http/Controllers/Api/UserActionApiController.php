<?php namespace App\Http\Controllers\Api;
/**
 * Copyright 2023 OpenStack Foundation
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

use App\Http\Controllers\Api\OAuth2\OAuth2ProtectedController;
use App\Http\Controllers\GetAllTrait;
use App\ModelSerializers\SerializerRegistry;
use Auth\Repositories\IUserActionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OAuth2\IResourceServerContext;
use utils\Filter;
use utils\FilterElement;
use Utils\Services\ILogService;

/**
 * Class UserActionApiController
 * @package App\Http\Controllers\Api
 */
final class UserActionApiController extends OAuth2ProtectedController
{
    use GetAllTrait;

    /**
     * UserActionApiController constructor.
     * @param IUserActionRepository $repository
     * @param IResourceServerContext $resource_server_context
     * @param ILogService $log_service
     */
    public function __construct
    (
        IUserActionRepository  $repository,
        IResourceServerContext $resource_server_context,
        ILogService            $log_service
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    protected function getFilterRules(): array
    {
        return [
            'realm' => ['=@', '=='],
            'user_action' => ['=@', '=='],
            'from_ip' => ['=@', '=='],
            'created_at' => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'realm' => 'nullable|string',
            'user_action' => 'nullable|string',
            'from_ip' => 'nullable|string',
            'created_at' => 'nullable|string',
        ];
    }

    /**
     * @return array
     */
    protected function getOrderRules(): array
    {
        return [
            'id',
            'realm',
            'user_action',
            'from_ip',
            'created_at'
        ];
    }

    protected function getAllSerializerType(): string
    {
        return SerializerRegistry::SerializerType_Private;
    }

    protected function serializerType(): string
    {
        return SerializerRegistry::SerializerType_Private;
    }

    protected function applyExtraFilters(Filter $filter): Filter
    {
        $current_user = Auth::user();
        if (!is_null($current_user))
            $filter->addFilterCondition(FilterElement::makeEqual("owner", $current_user->getId()));
        return $filter;
    }

    /**
     * @return JsonResponse
     */
    public function getActions(): JsonResponse
    {
        return $this->getAll();
    }
}