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
use App\Models\OAuth2\Factories\ApiScopeFactory;
use App\Services\AbstractService;
use models\exceptions\ValidationException;
use Models\OAuth2\Api;
use Models\OAuth2\ApiScope;
use models\utils\IEntity;
use OAuth2\Repositories\IApiRepository;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Services\IApiScopeService;
use Utils\Db\ITransactionService;
use models\exceptions\EntityNotFoundException;
/**
 * Class ApiScopeService
 * @package Services\OAuth2
 */
final class ApiScopeService extends AbstractService implements IApiScopeService
{

    /**
     * @var IApiScopeRepository
     */
    private $repository;

    /**
     * @var IApiRepository
     */
    private $api_repository;

    /**
     * ApiScopeService constructor.
     * @param ITransactionService $tx_service
     * @param IApiScopeRepository $repository
     * @param IApiRepository $api_repository
     */
    public function __construct
    (
        ITransactionService $tx_service,
        IApiScopeRepository $repository,
        IApiRepository $api_repository
    )
    {
        parent::__construct($tx_service);
        $this->repository     = $repository;
        $this->api_repository = $api_repository;
    }

    /**
     * @param array $scopes_names
     * @return array
     */
    public function getAudienceByScopeNames(array $scopes_names):array
    {
        $scopes   = $this->repository->getByNames($scopes_names);
        $audience = [];
        foreach ($scopes as $scope) {
            $api = $scope->getApi();
            $resource_server = !is_null($api) ? $api->getResourceServer() : null;
            if (!is_null($resource_server) && !array_key_exists($resource_server->getHost(), $audience)) {
                $audience[$resource_server->getHost()] = $resource_server->getId();
            }
        }
        return $audience;
    }

    /**
     * @param array $scopes_names
     * @return string
     */
    public function getStrAudienceByScopeNames(array $scopes_names):string
    {
        $audiences = $this->getAudienceByScopeNames($scopes_names);
        $audience  = '';
        foreach ($audiences as $resource_server_host => $ip) {
            $audience = $audience . $resource_server_host . ' ';
        }
        $audience = trim($audience);

        return $audience;
    }

    /**
     * @param $id
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $id, array $payload):IEntity
    {

        return $this->tx_service->transaction(function () use ($id, $payload) {
            //check that scope exists...
            $scope = $this->repository->getById($id);
            if (is_null($scope)) {
                throw new EntityNotFoundException(sprintf('scope id %s does not exists!', $id));
            }

            return ApiScopeFactory::populate($scope, $payload);
        });

    }

    /**
     * @param int $id
     * @throws EntityNotFoundException
     */
    public function delete(int $id):void
    {
        $this->tx_service->transaction(function () use ($id) {
            $scope = $this->repository->getById($id);
            if (is_null($scope)) {
                throw new EntityNotFoundException(sprintf('scope id %s does not exists!', $id));
            }
            $this->repository->delete($scope);
        });
    }

    /**
     * @param array $payload
     * @return ApiScope
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function create(array $payload):IEntity
    {

        return $this->tx_service->transaction(function () use ($payload) {

            $api_id = intval($payload['api_id']);
            $name = trim($payload['name']);

            $api = $this->api_repository->getById($api_id);
            // check if api exists...
            if (is_null($api) || !$api instanceof Api) {
                throw new EntityNotFoundException(sprintf('api id %s does not exists!.', $api_id));
            }

            $former_scope = $this->repository->getFirstByName($name);
            //check if we have a former scope with selected name
            if (!is_null($former_scope)) {
                throw new ValidationException(sprintf('scope name %s already exists.', $name));
            }

            $scope = ApiScopeFactory::build($payload);
            $api->addScope($scope);
            $this->repository->add($scope);
            return $scope;
        });
    }

}