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
use App\libs\Auth\Repositories\IBannedIPRepository;
use App\ModelSerializers\SerializerRegistry;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Utils\Services\IBannedIPService;
use Utils\Services\ILogService;
use Illuminate\Support\Facades\Request;
use Exception;
/**
 * Class ApiBannedIPController
 * @package App\Http\Controllers\Api
 */
final class ApiBannedIPController extends APICRUDController
{


    /**
     * ApiBannedIPController constructor.
     * @param IBannedIPRepository $banned_ip_repository
     * @param IBannedIPService $banned_ip_service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IBannedIPRepository $banned_ip_repository,
        IBannedIPService $banned_ip_service,
        ILogService $log_service
    )
    {

        parent::__construct($banned_ip_repository, $banned_ip_service, $log_service);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function get($id)
    {
        try {

            $ip = Request::input("ip", null);
            if (!is_null($ip)) {
                $banned_ip = $this->repository->getByIp(strval($ip));
            } else {
                $banned_ip = $this->repository->getById(intval($id));
            }
            if (is_null($banned_ip)) {
                throw new EntityNotFoundException();
            }
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($banned_ip)->serialize());
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
     * @param null $id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function delete($id = null)
    {
        try {
            if (is_null($id)) {
                $ip = Request::input("ip", null);
            } else {
                $banned_ip = $this->repository->getById($id);
                $ip        = $banned_ip->getIp();
            }
            if (is_null($ip))
                return $this->error400('invalid request');
            $this->service->deleteByIP($ip);
            return $this->deleted();
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
        return [];
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
        return [];
    }
}