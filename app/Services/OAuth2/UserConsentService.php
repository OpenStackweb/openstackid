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
use App\Services\AbstractService;
use Auth\Repositories\IUserRepository;
use Auth\User;
use Models\OAuth2\Client;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Services\IUserConsentService;
use Models\OAuth2\UserConsent;
use Utils\Db\ITransactionService;
use models\exceptions\EntityNotFoundException;
/**
 * Class UserConsentService
 * @package Services\OAuth2
 */
class UserConsentService extends AbstractService implements IUserConsentService
{

    /**
     * @var IUserRepository
     */
    private $user_repository;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * UserConsentService constructor.
     * @param IUserRepository $user_repository
     * @param IClientRepository $client_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IUserRepository $user_repository,
        IClientRepository $client_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->user_repository = $user_repository;
        $this->client_repository = $client_repository;
    }

    /**
     * @param User $user
     * @param Client $client
     * @param string $scopes
     * @return UserConsent
     * @throws EntityNotFoundException
     */
    public function addUserConsent(User $user, Client $client, string $scopes):UserConsent
    {
        return $this->tx_service->transaction(function() use($user, $client, $scopes){

            $scope_set = explode(' ', $scopes);
            sort($scope_set);
            $consent   = new UserConsent();
            $consent->setClient($client);
            $consent->setOwner($user);
            $consent->setScope(join(' ', $scope_set));
            $user->addConsent($consent);

            return $consent;
        });
    }
}