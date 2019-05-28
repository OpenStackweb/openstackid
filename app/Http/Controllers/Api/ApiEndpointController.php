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
use OAuth2\Repositories\IApiEndpointRepository;
use OAuth2\Services\IApiEndpointService;
use Utils\Services\ILogService;
/**
 * Class ApiEndpointController
 * REST Controller for Api endpoint entity CRUD ops
 */
final class ApiEndpointController extends APICRUDController {


    /**
     * ApiEndpointController constructor.
     * @param IApiEndpointService $api_endpoint_service
     * @param IApiEndpointRepository $endpoint_repository
     * @param ILogService $log_service
     */
    public function __construct
    (
        IApiEndpointService $api_endpoint_service,
        IApiEndpointRepository $endpoint_repository,
        ILogService $log_service
    )
    {
        parent::__construct($endpoint_repository, $api_endpoint_service, $log_service);
    }

    public function activate($id){
        try {
            $endpoint = $this->service->update($id,['active'=>false]);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($endpoint)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function deactivate($id){
        try {
            $endpoint = $this->service->update($id,['active'=>false]);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($endpoint)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function addRequiredScope($id, $scope_id){
        try {
            $endpoint = $this->service->addRequiredScope($id, $scope_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($endpoint)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    public function removeRequiredScope($id, $scope_id){
        try {
            $endpoint = $this->service->removeRequiredScope($id,$scope_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($endpoint)->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    protected function getFilterRules():array
    {
        return [
            'name'        => ['=@', '=='],
            'http_method' => ['=@', '=='],
            'route'       => ['=@', '=='],
            'active'      => [ '=='],
            'api_id'      => ['=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name'       => 'sometimes|required|string',
            'http_method'=> 'sometimes|required|string',
            'route'      => 'sometimes|required|string',
            'active'     => 'sometimes|required|boolean',
            'api_id'     => 'sometimes|required|integer',
        ];
    }
    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            'name'               => 'required|alpha_dash|max:255',
            'description'        => 'required|freetext',
            'active'             => 'required|boolean',
            'allow_cors'         => 'required|boolean',
            'route'              => 'required|route',
            'http_method'        => 'required|httpmethod',
            'api_id'             => 'required|integer',
            'rate_limit'         => 'required|integer',
        ];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [
            'name'               => 'sometimes|required|alpha_dash|max:255',
            'description'        => 'sometimes|required|freetext',
            'active'             => 'sometimes|required|boolean',
            'allow_cors'         => 'sometimes|required|boolean',
            'route'              => 'sometimes|required|route',
            'http_method'        => 'sometimes|required|httpmethod',
            'rate_limit'         => 'sometimes|integer',
        ];
    }
}