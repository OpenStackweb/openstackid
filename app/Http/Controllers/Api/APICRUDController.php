<?php namespace App\Http\Controllers;
/**
 * Copyright 2019 OpenStack Foundation
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
use App\Http\Controllers\Api\JsonController;
use App\ModelSerializers\SerializerRegistry;
use App\Services\IBaseService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use models\utils\IBaseRepository;
use Utils\Services\ILogService;
use Exception;
use models\exceptions\ValidationException;
use models\exceptions\EntityNotFoundException;
/**
 * Class APICRUDController
 * @package App\Http\Controllers
 */
abstract class APICRUDController extends JsonController
{
    use GetAllTrait;

    /**
     * @var IBaseRepository
     */
    protected $repository;

    /**
     * @var IBaseService
     */
    protected $service;

    /**
     * @param IBaseRepository $repository
     * @param IBaseService $service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IBaseRepository $repository,
        IBaseService $service,
        ILogService $log_service
    )
    {
        parent::__construct($log_service);
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * @param $id
     * @return string
     */
    protected  function getEntityNotFoundMessage($id):string {
        return sprintf("entity %s not found", $id);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get($id)
    {
        try {
            $entity = $this->repository->getById($id);
            if (is_null($entity)) {
                throw new EntityNotFoundException($this->getEntityNotFoundMessage($id));
            }

            return $this->ok(SerializerRegistry::getInstance()->getSerializer($entity, $this->serializerType())->serialize
            (
                Request::input("expand", '')
            ));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    protected function serializerType():string{
        return SerializerRegistry::SerializerType_Public;
    }

    /**
     * @return array
     */
    protected abstract function getUpdatePayloadValidationRules():array;

    /**
     * @return array
     */
    protected function getUpdatePayload():array{
        return request()->all();
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function update($id)
    {
        $payload = $this->getUpdatePayload();
        return $this->_update($id, $payload);
    }

    protected function curateUpdatePayload(array $payload):array {
        return $payload;
    }

    protected function curateCreatePayload(array $payload):array {
        return $payload;
    }

    protected function onUpdate($id, $payload){
        return $this->service->update($id, $payload);
    }
    /**
     * @param $id
     * @param array $payload
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    protected function _update($id, array $payload)
    {
        try {
            $rules = $this->getUpdatePayloadValidationRules();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);
            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            $entity = $this->onUpdate($id, $this->curateUpdatePayload($payload));

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($entity, $this->serializerType())->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return array
     */
    protected abstract function getCreatePayloadValidationRules():array;

    /**
     * @return array
     */
    protected function getCreatePayload():array{
        return Request::All();
    }

    /**
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function create()
    {
        try {
            $payload = $this->getCreatePayload();

            $rules = $this->getCreatePayloadValidationRules();
            // Creates a Validator instance and validates the data.
            $validation = Validator::make($payload, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            $entity = $this->service->create($this->curateCreatePayload($payload));

            return $this->created(SerializerRegistry::getInstance()->getSerializer($entity, $this->serializerType())->serialize());
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delete($id)
    {
        try {
            $this->service->delete($id);
            return $this->deleted();
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412($ex1->getMessages());
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message' => $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}