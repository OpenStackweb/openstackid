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

use App\Http\Utils\PagingConstants;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use OAuth2\Services\IClientPublicKeyService;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use Utils\Services\ILogService;
use OAuth2\Repositories\IClientPublicKeyRepository;
use Illuminate\Support\Facades\Request;

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
    protected function getCreatePayload(): array
    {
        $payload = Request::All();
        return array_merge($payload, $this->extra_create_payload_params);
    }

    /**
     * @param int $client_id
     * @return mixed
     */
    public function _getAll($client_id)
    {
        $values = Request::all();
        $rules = [
            'page' => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {
            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page = 1;
            $per_page = PagingConstants::DefaultPageSize;;

            if (Request::has('page')) {
                $page = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = new Filter();
            $filter->addFilterCondition(FilterElement::makeEqual("client_id", intval($client_id)));

            $data = $this->repository->getAllByPage(new PagingInfo($page, $per_page), $filter);
            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    [],
                    $this->getAllSerializerType()
                )
            );
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