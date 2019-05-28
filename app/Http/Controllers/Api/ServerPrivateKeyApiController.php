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
use OAuth2\Repositories\IServerPrivateKeyRepository;
use OAuth2\Services\IServerPrivateKeyService;
use Utils\Services\ILogService;
/**
 * Class ServerPrivateKeyApiController
 * @package App\Http\Controllers\Api
 */
final class ServerPrivateKeyApiController extends AsymmetricKeyApiController
{
    /**
     * @param IServerPrivateKeyRepository $repository
     * @param IServerPrivateKeyService $service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IServerPrivateKeyRepository $repository,
        IServerPrivateKeyService $service,
        ILogService $log_service
    )
    {
        parent::__construct($repository, $service, $log_service);
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
       return [
           'kid'         => 'required|text|min:5|max:255',
           'active'      => 'required|boolean',
           'valid_from'  => 'date_format:m/d/Y',
           'valid_to'    => 'date_format:m/d/Y|after:valid_from',
           'pem_content' => 'sometimes|required|private_key_pem:password|private_key_pem_length:password',
           'usage'       => 'required|public_key_usage',
           'type'        => 'required|public_key_type',
           'alg'         => 'required|key_alg:usage',
           'password'    => 'min:5|max:255|private_key_password:pem_content',
       ];
    }
}