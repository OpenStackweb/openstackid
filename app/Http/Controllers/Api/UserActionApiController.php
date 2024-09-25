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
use App\Http\Controllers\ParametrizedGetAll;
use App\Http\Controllers\Traits\ParseFilter;
use App\Http\Exceptions\HTTP401UnauthorizedException;
use App\ModelSerializers\SerializerRegistry;
use Auth\Repositories\IUserActionRepository;
use Illuminate\Http\JsonResponse;
use models\utils\IBaseRepository;
use OAuth2\IResourceServerContext;
use utils\Filter;
use utils\FilterElement;
use Utils\Services\IAuthService;
use Utils\Services\ILogService;

/**
 * Class UserActionApiController
 * @package App\Http\Controllers\Api
 */
final class UserActionApiController extends OAuth2ProtectedController
{
    use ParametrizedGetAll;

    use ParseFilter;

    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * UserActionApiController constructor.
     * @param IUserActionRepository $repository
     * @param IResourceServerContext $resource_server_context
     * @param IAuthService $auth_service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IUserActionRepository  $repository,
        IResourceServerContext $resource_server_context,
        IAuthService           $auth_service,
        ILogService            $log_service
    )
    {
        parent::__construct($resource_server_context, $log_service);
        $this->repository = $repository;
        $this->auth_service = $auth_service;
    }

    protected function getResourceServerContext(): IResourceServerContext
    {
        return $this->resource_server_context;
    }

    protected function getRepository(): IBaseRepository
    {
        return $this->repository;
    }

    /**
     * @return JsonResponse
     * @throws HTTP401UnauthorizedException
     */
    public function getActionsByCurrentUser(): JsonResponse
    {
        $user = $this->auth_service->getCurrentUser();
        if(is_null($user))
            throw new HTTP401UnauthorizedException();

        return $this->_getAll(
            function () {
                return [
                    'realm' => ['=@', '=='],
                    'user_action' => ['=@', '=='],
                    'from_ip' => ['=@', '=='],
                    'created_at' => ['<', '>'],
                ];
            },
            function () {
                return [
                    'realm' => 'nullable|string',
                    'user_action' => 'nullable|string',
                    'from_ip' => 'nullable|string',
                    'created_at' => 'nullable|string',
                ];
            },
            function () {
                return [
                    'id',
                    'realm',
                    'user_action',
                    'from_ip',
                    'created_at'
                ];
            },
            function ($filter) use($user) {
                if($filter instanceof Filter){
                     $filter->addFilterCondition(FilterElement::makeEqual('owner_id', $user->getId()));
                }
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }

    /**
     * @return JsonResponse
     */
    public function getActions(): JsonResponse
    {
         return $this->_getAll(
            function () {
                return [
                    'owner_id' => ['=='],
                    'realm' => ['=@', '=='],
                    'user_action' => ['=@', '=='],
                    'from_ip' => ['=@', '=='],
                    'created_at' => ['<', '>'],
                ];
            },
            function () {
                return [
                    'owner_id' => 'required|int',
                    'realm' => 'nullable|string',
                    'user_action' => 'nullable|string',
                    'from_ip' => 'nullable|string',
                    'created_at' => 'nullable|string',
                ];
            },
            function () {
                return [
                    'id',
                    'realm',
                    'user_action',
                    'from_ip',
                    'created_at'
                ];
            },
            function ($filter) {
                return $filter;
            },
            function () {
                return SerializerRegistry::SerializerType_Private;
            }
        );
    }
}