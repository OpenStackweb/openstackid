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
use OAuth2\Repositories\IApiRepository;
use OAuth2\Services\IApiService;
use Utils\Services\ILogService;
/**
 * Class ApiController
 * @package App\Http\Controllers\Api
 */
final class ApiController extends APICRUDController
{

    /**
     * ApiController constructor.
     * @param IApiRepository $api_repository
     * @param IApiService $api_service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IApiRepository $api_repository,
        IApiService $api_service,
        ILogService $log_service
    )
    {
        parent::__construct($api_repository, $api_service, $log_service);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function activate($id)
    {
        try {
            $api = $this->service->update($id, ['active' => true]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($api)->serialize());

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
    protected function getFilterRules():array{
        return [
            'resource_server_id' => ['==']
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'resource_server_id'   => 'sometimes|required|integer',
        ];
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deactivate($id)
    {
        try {
            $api = $this->service->update($id, ['active' => false]);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($api)->serialize());
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
            'name' => 'sometimes|required|alpha_dash|max:255',
            'description' => 'sometimes|required|text',
            'active' => 'sometimes|required|boolean',
        ];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [
            'name' => 'required|alpha_dash|max:255',
            'description' => 'required|text',
            'active' => 'required|boolean',
            'resource_server_id' => 'required|integer',
        ];
    }
}