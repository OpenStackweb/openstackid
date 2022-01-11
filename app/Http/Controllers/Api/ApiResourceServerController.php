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
use OAuth2\Repositories\IResourceServerRepository;
use OAuth2\Services\IResourceServerService;
use Utils\Services\ILogService;
/**
 * Class ApiResourceServerController
 * @package App\Http\Controllers\Api
 */
final class ApiResourceServerController extends APICRUDController
{

    /**
     * ApiResourceServerController constructor.
     * @param IResourceServerRepository $repository
     * @param IResourceServerService $resource_server_service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IResourceServerRepository $repository,
        IResourceServerService $resource_server_service,
        ILogService $log_service
    )
    {
        parent::__construct($repository, $resource_server_service, $log_service);
    }

    public function regenerateClientSecret($id)
    {
        try {
            $resource_server = $this->service->regenerateClientSecret($id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($resource_server->getClient())->serialize());
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


    public function activate($id)
    {
        try {
            $entity = $this->service->update($id, ['active' => true]);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity)->serialize());
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

    public function deactivate($id)
    {
        try {

            $entity = $this->service->update($id, ['active' => false]);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity)->serialize());
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

    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            'host' => 'required',
            'ips' => 'required',
            'friendly_name' => 'sometimes|required|text|max:512',
        ];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [
            'host' => 'required',
            'ips' => 'required',
            'friendly_name' => 'required|text|max:512',
            'active' => 'required|boolean',
        ];
    }
}