<?php namespace Services\OAuth2;
/**
 * Copyright 2016 OpenStack Foundation
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
use App\Models\OAuth2\Factories\ApiFactory;
use App\Services\AbstractService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\Api;
use Models\OAuth2\ResourceServer;
use models\utils\IEntity;
use OAuth2\Repositories\IApiRepository;
use OAuth2\Repositories\IResourceServerRepository;
use OAuth2\Services\IApiService;
use Utils\Db\ITransactionService;
/**
 * Class ApiService
 * @package Services\OAuth2
 */
final class ApiService extends AbstractService implements IApiService {

    /**
     * @var IApiRepository
     */
    private $api_repository;

    /**
     * @var IResourceServerRepository 
     */
    private $resource_server_repository;

    /**
     * ApiService constructor.
     * @param IResourceServerRepository $resource_server_repository
     * @param IApiRepository $api_repository
     * @param ITransactionService $tx_service
     */
	public function __construct
    (
        IResourceServerRepository $resource_server_repository,
        IApiRepository $api_repository,
        ITransactionService $tx_service
    ){
	    parent::__construct($tx_service);
	    $this->resource_server_repository = $resource_server_repository;
        $this->api_repository = $api_repository;
	}

    /**
     * @param int $id
     * @throws EntityNotFoundException
     */
    public function delete(int $id):void
    {

        $this->tx_service->transaction(function () use ($id) {
            $api = $this->api_repository->getById($id);
            if(is_null($api)) throw new EntityNotFoundException();
            $this->api_repository->delete($api);
        });
    }


    public function create(array $payload):IEntity
    {
	    return $this->tx_service->transaction(function () use ($payload) {

	        $name = trim($payload['name']);
            $resource_server_id = intval($payload['resource_server_id']);
            $resource_server = $this->resource_server_repository->getById($resource_server_id);
            if(is_null($resource_server) || !$resource_server instanceof ResourceServer)
                throw new EntityNotFoundException();
            $former_api = $this->api_repository->getByNameAndResourceServer($name, $resource_server_id);
            if(!is_null($former_api))
                throw new ValidationException(sprintf('api name %s already exists!', $name));

            $api = ApiFactory::build($payload);

            $resource_server->addApi($api);

            return $api;
        });

    }

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws \Exception
     */
    public function update(int $id, array $payload): IEntity {


	    return $this->tx_service->transaction(function () use ($id, $payload) {

            $api = $this->api_repository->getById($id);
            if(is_null($api) || !$api instanceof Api)
                throw new EntityNotFoundException(sprintf('api id %s does not exists!', $id));

            return ApiFactory::populate($api, $payload);
        });

    }

}