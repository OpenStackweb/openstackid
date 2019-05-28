<?php namespace App\Http\Controllers\Api;
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
use App\Http\Controllers\APICRUDController;
use App\ModelSerializers\SerializerRegistry;
use Exception;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Services\IApiScopeService;
use Utils\Services\ILogService;
/**
 * Class ApiScopeController
 */
final class ApiScopeController extends APICRUDController
{

    public function __construct
    (
        IApiScopeRepository $scope_repository,
        IApiScopeService $api_scope_service,
        ILogService $log_service
    )
    {
        parent::__construct($scope_repository, $api_scope_service, $log_service);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function activate($id)
    {
        try {
            $scope = $this->service->update($id, ['active' => true]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($scope)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deactivate($id)
    {
        try {
            $scope = $this->service->update($id, ['active' => false]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($scope)->serialize());
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            'id' => 'required|integer',
            'name' => 'sometimes|required|scopename|max:512',
            'description' => 'sometimes|required|freetext',
            'short_description' => 'sometimes|required|freetext|max:512',
            'active' => 'sometimes|required|boolean',
            'system' => 'sometimes|required|boolean',
            'default' => 'sometimes|required|boolean',
            'assigned_by_groups' => 'sometimes|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [
            'name' => 'required|scopename|max:512',
            'short_description' => 'required|freetext|max:512',
            'description' => 'required|freetext',
            'active' => 'required|boolean',
            'default' => 'required|boolean',
            'system' => 'required|boolean',
            'api_id' => 'required|integer',
            'assigned_by_groups' => 'required|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name'                  => ['=@', '=='],
            'is_assigned_by_groups' => ['=='],
            'api_id'                => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name'                  => 'sometimes|required|string',
            'is_assigned_by_groups' => 'sometimes|required|boolean',
            'api_id'                => 'sometimes|required|integer',
        ];
    }

}