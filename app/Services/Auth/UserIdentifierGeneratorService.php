<?php namespace App\Services\Auth;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Services\AbstractService;
use Auth\IUserNameGeneratorService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Utils\Db\ITransactionService;
/**
 * Class UserIdentifierGeneratorService
 * @package App\Services\Auth
 */
final class UserIdentifierGeneratorService
    extends AbstractService
    implements IUserIdentifierGeneratorService
{
    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IUserNameGeneratorService
     */
    private $name_generator_service;

    /**
     * UserIdentifierGeneratorService constructor.
     * @param IUserRepository $user_repository
     * @param IUserNameGeneratorService $name_generator_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        IUserNameGeneratorService $name_generator_service,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->name_generator_service = $name_generator_service;
        $this->user_repository = $user_repository;
    }

    /**
     * @param User $user
     * @return User
     * @throws \Exception
     */
    public function generateIdentifier(User $user): User
    {
        return $this->tx_service->transaction(function () use ($user) {
            if($user->hasIdentifier()) return $user;
            $fragment_nbr = 1;
            $this->name_generator_service->generate($user);
            $identifier = $original_identifier = $user->getIdentifier();
            do {
                $old_user = $this->user_repository->getByIdentifier($identifier);
                if (!is_null($old_user)) {
                    $identifier = $original_identifier . IUserNameGeneratorService::USER_NAME_CHAR_CONNECTOR . $fragment_nbr;
                    $fragment_nbr++;
                    continue;
                }
                $user->setIdentifier($identifier);
                break;
            } while (1);

            return $user;
        });
    }

}