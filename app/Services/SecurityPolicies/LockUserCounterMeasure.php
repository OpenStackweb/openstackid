<?php namespace Services\SecurityPolicies;
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

use App\Jobs\AddUserAction;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Exception;
use Illuminate\Support\Facades\Log;
use OpenId\Services\IUserService;
use Utils\Db\ITransactionService;
use Utils\IPHelper;
use Utils\Services\ISecurityPolicyCounterMeasure;
use Utils\Services\IServerConfigurationService;
/**
 * Class LockUserCounterMeasure
 * @package Services\SecurityPolicies
 */
class LockUserCounterMeasure implements ISecurityPolicyCounterMeasure
{
    /**
     * @var IServerConfigurationService
     */
    private $server_configuration;
    /**
     * @var IUserService
     */
    private $user_service;
    /**
     * @var IUserRepository
     */
    private $repository;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * LockUserCounterMeasure constructor.
     * @param IUserRepository $repository
     * @param IUserService $user_service
     * @param IServerConfigurationService $server_configuration
     * @param ITransactionService $tx_service
     */
    public function __construct(
        IUserRepository $repository,
        IUserService $user_service,
        IServerConfigurationService $server_configuration,
        ITransactionService $tx_service
    ) {
        $this->user_service = $user_service;
        $this->server_configuration = $server_configuration;
        $this->repository = $repository;
        $this->tx_service = $tx_service;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function trigger(array $params = [])
    {
        return $this->tx_service->transaction(function() use($params){
            try {
                if (isset($params["user_id"])) {
                    $user_id = $params["user_id"];
                    $user    = $this->repository->getById($user_id);
                    $max_login_failed_attempts = intval($this->server_configuration->getConfigValue("MaxFailed.Login.Attempts"));
                    if ($user instanceof User) {
                        //apply lock policy
                        if (intval($user->getLoginFailedAttempt()) < $max_login_failed_attempts) {
                            $this->user_service->updateFailedLoginAttempts($user->getId());
                            return $this;
                        }

                        $action = sprintf
                        (
                            "Locked due to too many failed login attempts (%s)",
                            $max_login_failed_attempts
                        );

                        AddUserAction::dispatch($user->getId(), IPHelper::getUserIp(), $action);

                        $this->user_service->lockUser($user->getId());
                    }
                }
            } catch (Exception $ex) {
                Log::error($ex);
            }
            return $this;
        });
    }
}