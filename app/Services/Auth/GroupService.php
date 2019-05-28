<?php namespace App\Services\Auth;
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
use App\libs\Auth\Factories\GroupFactory;
use App\libs\Auth\Repositories\IGroupRepository;
use App\Services\AbstractService;
use Auth\Group;
use Auth\Repositories\IUserRepository;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use Utils\Db\ITransactionService;
/**
 * Class GroupService
 * @package App\Services\Auth
 */
final class GroupService extends AbstractService implements IGroupService
{
    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * GroupService constructor.
     * @param IGroupRepository $group_repository
     * @param IUserRepository $user_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IGroupRepository $group_repository,
        IUserRepository $user_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->user_repository  = $user_repository;
        $this->group_repository = $group_repository;
    }

    /**
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function create(array $payload): IEntity
    {
        return $this->tx_service->transaction(function() use($payload){
            $name = trim($payload['name']);
            $slug  = trim($payload['slug']);

            $formerGroup = $this->group_repository->getOneByName($name);
            if(!is_null($formerGroup)){
                throw new ValidationException(sprintf("there is already a group with name %s", $name));
            }

            $formerGroup = $this->group_repository->getOneBySlug($slug);
            if(!is_null($formerGroup)){
                throw new ValidationException(sprintf("there is already a group with slug %s", $slug));
            }

            $group = GroupFactory::build($payload);

            $this->group_repository->add($group);

            return $group;
        });
    }

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $id, array $payload): IEntity
    {
        return $this->tx_service->transaction(function() use($id, $payload){
            $group = $this->group_repository->getById($id);

            if(is_null($group) || !$group instanceof Group)
                throw new EntityNotFoundException("group not found!");

            $name = trim($payload['name']);
            $slug = trim($payload['slug']);

            $formerGroup = $this->group_repository->getOneByName($name);
            if(!is_null($formerGroup) && $formerGroup->getId() != $group->getId()){
                throw new ValidationException(sprintf("there is already a group with name %s", $name));
            }

            $formerGroup = $this->group_repository->getOneBySlug($slug);
            if(!is_null($formerGroup)  && $formerGroup->getId() != $group->getId()){
                throw new ValidationException(sprintf("there is already a group with slug %s", $slug));
            }

            return GroupFactory::populate($group, $payload);

        });
    }


    /**
     * @param int $id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function delete(int $id): void
    {
        $this->tx_service->transaction(function() use($id) {
            $group = $this->group_repository->getById($id);
            if(is_null($group) || !$group instanceof Group)
                throw new EntityNotFoundException("group not found");

            $this->group_repository->delete($group);
        });
    }

    /**
     * @param Group $group
     * @param int $user_id
     * @throw EntityNotFoundException
     * @throw ValidationException
     */
    public function addUser2Group(Group $group, int $user_id): void
    {
       $this->tx_service->transaction(function() use($group, $user_id){
            $user = $this->user_repository->getById($user_id);
            if(is_null($user))
                throw new EntityNotFoundException();

            $user->addToGroup($group);
       });
    }

    /**
     * @param Group $group
     * @param int $user_id
     * @throw EntityNotFoundException
     * @throw ValidationException
     */
    public function removeUserFromGroup(Group $group, int $user_id): void
    {
        $this->tx_service->transaction(function() use($group, $user_id){
            $user = $this->user_repository->getById($user_id);
            if(is_null($user))
                throw new EntityNotFoundException();

            $user->removeFromGroup($group);
        });
    }
}