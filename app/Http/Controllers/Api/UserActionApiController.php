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
use App\Http\Controllers\Traits\ParseFilter;
use App\Models\Exceptions\AuthzException;
use App\ModelSerializers\SerializerRegistry;
use Auth\Repositories\IUserActionRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\IResourceServerContext;
use Utils\Services\ILogService;

/**
 * Class UserActionApiController
 * @package App\Http\Controllers\Api
 */
final class UserActionApiController extends OAuth2ProtectedController
{
    use GetAllTrait;

    use ParseFilter;

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
            'owner_id' => ['=='],
            'realm' => ['=@', '=='],
            'user_action' => ['=@', '=='],
            'from_ip' => ['=@', '=='],
            'created_at' => ['<', '>'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules(): array
    {
        return [
            'owner_id' => 'required|int',
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

    /**
     * @return JsonResponse
     */
    public function getActions(): JsonResponse
    {
        try {
            $filter = $this->getFilter($this->getFilterRules(), $this->getFilterValidatorRules());
            $current_user = Auth::user();
            $owner_id = intval($filter->getUniqueFilter("owner_id")->getValue());
            if ($current_user->getId() != $owner_id && !$current_user->isAdmin()) {
                throw new AuthzException("Current user owner mismatch.");
            }
            return $this->getAll();
        }
        catch (AuthzException $ex){
            Log::warning($ex);
            return $this->error403();
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}