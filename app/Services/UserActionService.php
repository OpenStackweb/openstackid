<?php namespace Services;
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
use Auth\Repositories\IUserRepository;
use Exception;
use models\exceptions\EntityNotFoundException;
use Models\UserAction;
use Illuminate\Support\Facades\Log;
use Utils\Db\ITransactionService;
/**
 * Class UserActionService
 * @package Services
 */
final class UserActionService implements IUserActionService
{
    /**
     * @var IUserRepository
     */
    private $user_repository;
    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * UserActionService constructor.
     * @param IUserRepository $user_repository
     * @param ITransactionService $tx_service
     */
    public function __construct(IUserRepository $user_repository, ITransactionService $tx_service)
    {
        $this->user_repository = $user_repository;
        $this->tx_service = $tx_service;
    }

    /**
     * @param $user_id
     * @param $ip
     * @param $user_action
     * @param $realm
     * @param $user_email
     * @return UserAction
     * @throws Exception
     */
    public function addUserAction($user_id, $ip, $user_action, $realm = null, $user_email = null): UserAction
    {
        return $this->tx_service->transaction(function () use ($user_id, $ip, $user_action, $realm, $user_email) {

            Log::debug(sprintf("UserActionService::addUserAction user %s action %s ip %s", $user_id, $user_action, $ip));
            $action = new UserAction();
            $action->setFromIp($ip);
            $action->setUserAction($user_action);

            if(!empty($realm))
                $action->setRealm($realm);
            // try to get the user by if
            $user = $this->user_repository->getById($user_id);
            // and if not , by email
            if(is_null($user) && !empty($user_email))
                $user = $this->user_repository->getByEmailOrName($user_email);

            if (is_null($user))
                throw new EntityNotFoundException();

            $user->addUserAction($action);

            return $action;
        });

    }
} 