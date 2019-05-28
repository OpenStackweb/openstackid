<?php namespace Services\OpenId;
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
use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Repositories\IGroupRepository;
use App\Services\AbstractService;
use Auth\IUserNameGeneratorService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use OpenId\Services\IUserService;
use phpDocumentor\Reflection\Types\Parent_;
use Utils\Db\ITransactionService;
use Utils\Services\ILogService;
use Utils\Services\IServerConfigurationService;
/**
 * Class UserService
 * @package Services\OpenId
 */
final class UserService extends AbstractService implements IUserService
{

     /**
     * @var IUserRepository
     */
    private $repository;
    /**
     * @var ILogService
     */
    private $log_service;

    /**
     * @var IUserNameGeneratorService
     */
    private $user_name_generator;

    /**
     * @var IServerConfigurationService
     */
    private $configuration_service;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * UserService constructor.
     * @param IUserRepository $repository
     * @param IGroupRepository $group_repository
     * @param IUserNameGeneratorService $user_name_generator
     * @param ITransactionService $tx_service
     * @param IServerConfigurationService $configuration_service
     * @param ILogService $log_service
     */
    public function __construct
    (
        IUserRepository $repository,
        IGroupRepository $group_repository,
        IUserNameGeneratorService $user_name_generator,
        ITransactionService $tx_service,
        IServerConfigurationService $configuration_service,
        ILogService $log_service
    )
    {
        parent::__construct($tx_service);
        $this->repository            = $repository;
        $this->group_repository      = $group_repository;
        $this->user_name_generator   = $user_name_generator;
        $this->configuration_service = $configuration_service;
        $this->log_service           = $log_service;
    }

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function updateLastLoginDate(int $user_id):User
    {
        return $this->tx_service->transaction(function() use($user_id){
            $user = $this->repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) throw new EntityNotFoundException();
            $user->updateLastLoginDate();
            return $user;
        });
    }

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function updateFailedLoginAttempts(int $user_id):User
    {
         return $this->tx_service->transaction(function() use($user_id){
            $user = $this->repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) throw new EntityNotFoundException();
            $user->updateLoginFailedAttempt();
            return $user;
        });
    }

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function lockUser(int $user_id):User
    {
        return $this->tx_service->transaction(function() use($user_id){
            $user = $this->repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) throw new EntityNotFoundException();
            return $user->lock();
        });
    }

    /**
     * @param int $user_id
     * @return void
     * @throws EntityNotFoundException
     */
    public function unlockUser(int $user_id):User
    {
        return $this->tx_service->transaction(function() use($user_id){
            $user = $this->repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) throw new EntityNotFoundException();
            return $user->unlock();
        });
    }

    /**
     * @param int $user_id
     * @param bool $show_pic
     * @param bool $show_full_name
     * @param bool $show_email
     * @param string $identifier
     * @return User
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function saveProfileInfo($user_id, $show_pic, $show_full_name, $show_email, $identifier):User
    {

        return $this->tx_service->transaction(function() use($user_id, $show_pic, $show_full_name, $show_email, $identifier){
            $user = $this->repository->getById($user_id);
            if(is_null($user) || !$user instanceof User) throw new EntityNotFoundException();

            $former_user = $this->repository->getByIdentifier($identifier);

            if(!is_null($former_user) && $former_user->getId() != $user_id){
                throw new ValidationException("there is already another user with that openid identifier");
            }

            $user->setPublicProfileShowPhoto($show_pic);
            $user->setPublicProfileShowFullname($show_full_name);
            $user->setPublicProfileShowEmail($show_email);
            $user->setIdentifier($identifier);

            return $user;
        });
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
            if(isset($payload["email"])){
                $former_user = $this->repository->getByEmailOrName(trim($payload["email"]));
                if(!is_null($former_user))
                    throw new ValidationException(sprintf("email %s already belongs to another user", $payload["email"]));
            }

            if(isset($payload["identifier"])){
                $former_user = $this->repository->getByIdentifier(trim($payload["identifier"]));
                if(!is_null($former_user))
                    throw new ValidationException(sprintf("identifier %s already belongs to another user", $payload["identifier"]));
            }

            $user = UserFactory::build($payload);

            if(isset($payload['groups'])){
                foreach($payload['groups'] as $group_id) {
                    $group = $this->group_repository->getById($group_id);
                    if(is_null($group))
                        throw new EntityNotFoundException("group not found");
                    $user->addToGroup($group);
                }
            }

            $this->repository->add($user);

            return $user;
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
            $user = $this->repository->getById($id);
            if(is_null($user) || !$user instanceof User)
                throw new EntityNotFoundException("user not found");

            if(isset($payload["email"])){
                $former_user = $this->repository->getByEmailOrName(trim($payload["email"]));
                if(!is_null($former_user) && $former_user->getId() != $id)
                    throw new ValidationException(sprintf("email %s already belongs to another user", $payload["email"]));
            }

            if(isset($payload["identifier"])){
                $former_user = $this->repository->getByIdentifier(trim($payload["identifier"]));
                if(!is_null($former_user) && $former_user->getId() != $id)
                    throw new ValidationException(sprintf("identifier %s already belongs to another user", $payload["identifier"]));
            }

            $user = UserFactory::populate($user, $payload);

            if(isset($payload['groups'])){
                $user->clearGroups();
                foreach($payload['groups'] as $group_id) {
                    $group = $this->group_repository->getById($group_id);
                    if(is_null($group))
                        throw new EntityNotFoundException("group not found");
                    $user->addToGroup($group);
                }
            }

            return $user;

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
            $user = $this->repository->getById($id);
            if(is_null($user) || !$user instanceof User)
                throw new EntityNotFoundException("user not found");

            $this->repository->delete($user);
        });
    }
}