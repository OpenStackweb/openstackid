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
use App\Models\OAuth2\Factories\ApiScopeGroupFactory;
use Auth\Repositories\IUserRepository;
use models\exceptions\EntityNotFoundException;
use models\utils\IEntity;
use OAuth2\Exceptions\InvalidApiScopeGroup;
use OAuth2\Repositories\IApiScopeRepository;
use OAuth2\Services\IApiScopeGroupService;
use OAuth2\Repositories\IApiScopeGroupRepository;
use OAuth2\Services\IApiScopeService;
use Utils\Services\ILogService;
use Utils\Db\ITransactionService;
use Models\OAuth2\ApiScopeGroup;
/**
 * Class ApiScopeGroupService
 * @package Services\OAuth2
 */
final class ApiScopeGroupService implements IApiScopeGroupService
{

    /**
     * @var IApiScopeGroupRepository
     */
    private $repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var ILogService
     */
    private $log_service;

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IApiScopeService
     */
    private $scope_service;

    /**
     * @var IApiScopeRepository
     */
    private $scope_repository;


    public function __construct
    (
        IApiScopeGroupRepository $repository,
        IApiScopeService $scope_service,
        IUserRepository $user_repository,
        IApiScopeRepository $scope_repository,
        ITransactionService $tx_service,
        ILogService $log_service
    )
    {
        $this->log_service      = $log_service;
        $this->repository       = $repository;
        $this->user_repository  = $user_repository;
        $this->scope_service    = $scope_service;
        $this->scope_repository = $scope_repository;
        $this->tx_service       = $tx_service;
    }

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws \Exception
     */
    public function update(int $id, array $payload):IEntity
    {
        return $this->tx_service->transaction(function () use ($id, $payload) {

            $group = $this->repository->getById($id);

            if (is_null($group) ||!$group instanceof ApiScopeGroup)
            {
                throw new InvalidApiScopeGroup(sprintf('api scope group id %s does not exists!', $id));
            }

            if(isset($payload['name'])){
                $former_group = $this->repository->getByName($payload['name']);
                if(!is_null($former_group) && $former_group->getId() != $id)
                {
                    throw new InvalidApiScopeGroup(sprintf('there is already another api scope group name (%s).', $payload['name']));
                }
            }

            ApiScopeGroupFactory::populate($group, $payload);

            if(isset($payload['users'])){
                $group->clearUsers();
                $users = explode(',', $payload['users']);
                foreach($users as $user_id)
                {
                    $user = $this->user_repository->getById(intval($user_id));
                    if(is_null($user)) throw new EntityNotFoundException(sprintf('user %s not found.',$user_id));
                    $group->addUser($user);
                }
            }

            if(isset($payload['scopes'])){
                $scopes = explode(',', $payload['scopes']);
                foreach($scopes as $scope_id)
                {
                    $scope = $this->scope_repository->getById(intval($scope_id));
                    if(is_null($scope)) throw new EntityNotFoundException(sprintf('scope %s not found.',$scope_id));
                    $group->addScope($scope);
                }
            }

            return $group;
        });
    }

    /**
     * @param array $payload
     * @return IEntity
     */
    public function create(array $payload):IEntity
    {
        return $this->tx_service->transaction(function () use ($payload) {

            $name         = trim($payload['name']);
            $former_group = $this->repository->getByName($name);

            if(!is_null($former_group))
            {
                throw new InvalidApiScopeGroup(sprintf('there is already another api scope group name (%s).', $name));
            }
            $group  = ApiScopeGroupFactory::build($payload);
            $scopes = $payload['scopes'];
            $users  = $payload['users'];
            $scopes = explode(',', $scopes);
            $users  = explode(',', $users);

            foreach($scopes as $scope_id)
            {
                $scope = $this->scope_repository->getById(intval($scope_id));
                if(is_null($scope)) throw new EntityNotFoundException(sprintf('scope %s not found.',$scope_id));
                $group->addScope($scope);
            }

            foreach($users as $user_id)
            {
                $user = $this->user_repository->getById(intval($user_id));
                if(is_null($user)) throw new EntityNotFoundException(sprintf('user %s not found.',$user_id));
                $group->addUser($user);
            }

            $this->repository->add($group);

            return $group;
        });
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $this->tx_service->transaction(function () use ($id) {

            $group = $this->repository->getById($id);

            if(is_null($group))
                throw new EntityNotFoundException();

            $this->repository->delete($group);

        });
    }
}