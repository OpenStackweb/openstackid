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
use App\Models\OAuth2\Factories\ApiEndpointFactory;
use App\Services\AbstractService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\Api;
use Models\OAuth2\ApiScope;
use models\utils\IEntity;
use OAuth2\Repositories\IApiEndpointRepository;
use OAuth2\Repositories\IApiRepository;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Services\IApiEndpointService;
use Models\OAuth2\ApiEndpoint;
use Utils\Db\ITransactionService;
/**
 * Class ApiEndpointService
 * @package Services\OAuth2
 */
final class ApiEndpointService extends AbstractService implements IApiEndpointService {

    /**
     * @var IApiEndpointRepository
     */
    private $repository;

    /**
     * @var IApiScopeRepository
     */
    private $scope_repository;

    /**
     * @var IApiRepository
     */
    private $api_repository;

    /**
     * ApiEndpointService constructor.
     * @param ITransactionService $tx_service
     * @param IApiEndpointRepository $repository
     * @param IApiScopeRepository $scope_repository
     * @param IApiRepository $api_repository
     */
	public function __construct
    (
        ITransactionService $tx_service,
        IApiEndpointRepository $repository,
        IApiScopeRepository $scope_repository,
        IApiRepository $api_repository
    ){
	    parent::__construct($tx_service);
		$this->repository       = $repository;
        $this->scope_repository = $scope_repository;
        $this->api_repository   = $api_repository;
	}


    /**
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function create(array $payload): IEntity
    {
        return $this->tx_service->transaction(function () use ($payload) {

            //check that does not exists an endpoint with same http method and same route
            $route = trim($payload['route']);
            $http_method = trim($payload['http_method']);
            $api_id = intval($payload['api_id']);
            $api = $this->api_repository->getById($api_id);
            if(is_null($api) || !$api instanceof Api)
                throw new EntityNotFoundException();

            $former_endpoint = $this->repository->getApiEndpointByUrlAndMethodAndApi($route, $http_method, $api);

            if(!is_null($former_endpoint))
                throw new ValidationException
                (
                    sprintf
                    (
                        'there is already an endpoint api with route %s and http method %s',
                        $route,
                        $http_method
                    )
                );

            $endpoint = ApiEndpointFactory::build($payload);
            $api->addEndpoint($endpoint);

            return $endpoint;
        });

    }

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(int $id, array $payload):IEntity {

	    return $this->tx_service->transaction(function () use ($id, $payload){
            $endpoint = $this->repository->getById($id);

            if(is_null($endpoint) || !$endpoint instanceof ApiEndpoint)
                throw new EntityNotFoundException(sprintf('api endpoint id %s does not exists!', $id));


            //check that does not exists an endpoint with same http method and same route
            $former_endpoint = $this->repository->getApiEndpointByUrlAndMethodAndApi
            (
                $endpoint->getRoute(),
                $endpoint->getHttpMethod(),
                $endpoint->getApi()
            );

            if(!is_null($former_endpoint) && $former_endpoint->getId() != $endpoint->getId())
                throw new ValidationException
                (
                    sprintf
                    (
                        'there is already an endpoint api with route %s and http method %s',
                        $endpoint->getRoute(),
                        $endpoint->getHttpMethod()
                    )
                );

            return ApiEndpointFactory::populate($endpoint, $payload);
        });
    }

    /**
     * Adds a new required scope to a given api endpoint,
     * given scope must belongs to owner api of the given endpoint
     * @param int $api_endpoint_id
     * @param int $scope_id
     * @return ApiEndpoint
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addRequiredScope(int $api_endpoint_id, int $scope_id):ApiEndpoint
    {

	    return $this->tx_service->transaction(function () use($api_endpoint_id, $scope_id){

            $api_endpoint = $this->repository->getById($api_endpoint_id);

            if(is_null($api_endpoint) || !$api_endpoint instanceof ApiEndpoint)
                throw new EntityNotFoundException(sprintf("api endpoint id %s does not exists!.",$api_endpoint_id));

            $scope = $this->scope_repository->getById($scope_id);

            if(is_null($scope) || !$scope instanceof ApiScope)
                throw new EntityNotFoundException(sprintf("api scope id %s does not exists!.", $scope_id));

            if($scope->getApi()->getId() != $api_endpoint->getApi()->getId())
                throw new ValidationException(sprintf("api scope id %s does not belong to api id %s !.",$scope_id, $api_endpoint->getApi()->getId()));

            if($api_endpoint->hasScope($scope))
                throw new ValidationException(sprintf("api scope id %s already belongs to endpoint id %s!.",$scope_id,$api_endpoint->getId()));

            $api_endpoint->addScope($scope);

            return $api_endpoint;
        });
    }


    /**
     * Remove a required scope to a given api endpoint,
     * given scope must belongs to owner api of the given endpoint
     * @param int $api_endpoint_id
     * @param int $scope_id
     * @return ApiEndpoint
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeRequiredScope(int $api_endpoint_id, int $scope_id):ApiEndpoint{


	    return $this->tx_service->transaction(function () use($api_endpoint_id, $scope_id){

            $api_endpoint = $this->repository->getById($api_endpoint_id);

            if(is_null($api_endpoint) || !$api_endpoint instanceof ApiEndpoint)
                throw new EntityNotFoundException(sprintf("api endpoint id %s does not exists!.",$api_endpoint_id));

            $scope = $this->scope_repository->getById($scope_id);

            if(is_null($scope) || !$scope instanceof ApiScope)
                throw new EntityNotFoundException(sprintf("api scope id %s does not exists!.",$scope_id));

            if($scope->getApi()->getId() !== $api_endpoint->getApi()->getId())
                throw new ValidationException(sprintf("api scope id %s does not belongs to api id %s!.",$scope_id,$api_endpoint->getApi()->getId()));


            if(!$api_endpoint->hasScope($scope))
                throw new ValidationException(sprintf("api scope id %s does not belongs to endpoint id %s !.",$scope_id,$api_endpoint->getId()));

            $api_endpoint->removeScope($scope);

            return $api_endpoint;
        });

    }

    /**
     * deletes a given api endpoint
     * @param int $id
     * @throws EntityNotFoundException
     */
    public function delete(int $id):void
    {
	     $this->tx_service->transaction(function () use ($id) {
            $endpoint = $this->repository->getById($id);
            if(is_null($endpoint)) throw new EntityNotFoundException();
            $this->repository->delete($endpoint);
        });
    }

}