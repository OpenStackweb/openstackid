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
use OAuth2\Services\IClientPublicKeyService;
use Utils\Services\ILogService;
use OAuth2\Repositories\IClientPublicKeyRepository;
use Illuminate\Support\Facades\Input;
/**
 * Class ClientPublicKeyApiController
 * @package App\Http\Controllers\Api
 */
final class ClientPublicKeyApiController extends AsymmetricKeyApiController
{
    /**
     * @param IClientPublicKeyRepository $repository
     * @param IClientPublicKeyService $service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IClientPublicKeyRepository $repository,
        IClientPublicKeyService $service,
        ILogService $log_service
    )
    {
        parent::__construct($repository, $service, $log_service);
    }

    /**
     * @return array
     */
    protected function getCreatePayload():array{
        $payload =  Input::All();
        return array_merge($payload, $this->extra_create_payload_params);
    }

    private $extra_create_payload_params = [];
    /**
     * @param int $client_id
     * @return mixed
     */
    public function _create($client_id)
    {
        $this->extra_create_payload_params['client_id'] = $client_id;
        return $this->create();
    }

    /**
     * @param int $client_id
     * @param int $public_key_id
     * @return mixed
     */
    public function _update($client_id, $public_key_id)
    {
        return $this->update($public_key_id);
    }

    /**
     * @param int $client_id
     * @param int $public_key_id
     * @return mixed
     */
    public function _delete($client_id, $public_key_id){
        return $this->delete($public_key_id);
    }

    /**
     * @return array
     */
    protected function getCreatePayloadValidationRules(): array
    {
            return [
                'client_id'   => 'required|integer',
                'kid'         => 'required|text|max:255',
                'active'      => 'required|boolean',
                'valid_from'  => 'required|date_format:m/d/Y',
                'valid_to'    => 'required|date_format:m/d/Y|after:valid_from',
                'pem_content' => 'required|public_key_pem|public_key_pem_length',
                'usage'       => 'required|public_key_usage',
                'type'        => 'required|public_key_type',
                'alg'         => 'required|key_alg:usage',
            ];
    }
}