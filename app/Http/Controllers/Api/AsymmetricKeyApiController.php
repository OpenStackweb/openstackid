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
use OAuth2\Services\IAsymmetricKeyService;
use models\exceptions\EntityNotFoundException;
use Utils\Services\ILogService;
use OAuth2\Repositories\IAsymmetricKeyRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Exception;
/**
 * Class AsymmetricKeyApiController
 * @package App\Http\Controllers\Api
 */
abstract class AsymmetricKeyApiController extends APICRUDController
{
    /**
     * @var IAsymmetricKeyService
     */
    protected $service;

    /**
     * @var IAsymmetricKeyRepository
     */
    protected $repository;

    /**
     * @param IAsymmetricKeyRepository $repository
     * @param IAsymmetricKeyService $service
     * @param ILogService $log_service
     */
    public function __construct(
        IAsymmetricKeyRepository $repository,
        IAsymmetricKeyService $service,
        ILogService $log_service
    ) {
        parent::__construct($repository, $service, $log_service);
    }

    /**
     * @return array
     */
    protected function getUpdatePayloadValidationRules(): array
    {
        return [
            'id'     => 'required|integer',
            'active' => 'required|boolean',
        ];
    }



}