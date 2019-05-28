<?php namespace Services\Utils;
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
use App\Http\Utils\IUserIPHelperProvider;
use App\libs\Auth\Repositories\IBannedIPRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Models\BannedIP;
use Exception;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;
use Utils\Db\ITransactionService;
use Utils\Services\IAuthService;
use Utils\Services\IBannedIPService;
use Utils\Services\ICacheService;
use Utils\Services\IServerConfigurationService;
/**
 * Class BannedIPService
 * @package Utils\Services
 */
final class BannedIPService extends AbstractService implements IBannedIPService
{

    /**
     * @var ICacheService
     */
    private $cache_service;
    /**
     * @var IServerConfigurationService
     */
    private $server_configuration_service;
    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * @var IUserIPHelperProvider
     */
    private $ip_helper;

    /**
     * @var IBannedIPRepository
     */
    private $banned_ip_repository;

    /**
     * BannedIPService constructor.
     * @param ICacheService $cache_service
     * @param IServerConfigurationService $server_configuration_service
     * @param IAuthService $auth_service
     * @param IUserIPHelperProvider $ip_helper
     * @param ITransactionService $tx_service
     */
    public function __construct(
        ICacheService $cache_service,
        IServerConfigurationService $server_configuration_service,
        IAuthService $auth_service,
        IUserIPHelperProvider $ip_helper,
        IBannedIPRepository $banned_ip_repository,
        ITransactionService $tx_service
    ) {

        parent::__construct($tx_service);
        $this->cache_service = $cache_service;
        $this->server_configuration_service = $server_configuration_service;
        $this->ip_helper = $ip_helper;
        $this->banned_ip_repository = $banned_ip_repository;
        $this->auth_service = $auth_service;
    }

    /**
     * @param int $initial_hits
     * @param string $exception_type
     * @return BannedIP
     * @throws Exception
     */
    public function add(int $initial_hits, string $exception_type):BannedIP
    {
        return $this->tx_service->transaction(function() use ($initial_hits, $exception_type) {
            $banned_ip = null;
            try {
                $remote_address = $this->ip_helper->getCurrentUserIpAddress();
                //try to create on cache
                $this->cache_service->addSingleValue($remote_address, $initial_hits,
                    intval($this->server_configuration_service->getConfigValue("BlacklistSecurityPolicy.BannedIpLifeTimeSeconds")));

                    $banned_ip = $this->banned_ip_repository->getByIp($remote_address);
                    if (is_null($banned_ip)) {
                        $banned_ip = new BannedIP();
                        $banned_ip->setHits($remote_address);
                    }
                    $banned_ip->setExceptionType($exception_type);
                    $banned_ip->setHits($initial_hits);

                    if (Auth::check()) {
                        $banned_ip->setUser(Auth::user());
                    }

                    if($banned_ip->isNew())
                        $this->banned_ip_repository->add($banned_ip);


            }
            catch (Exception $ex) {
               Log::error($ex);
            }
            return $banned_ip;
        });
    }

    /**
     * @param string $ip
     * @throws Exception
     */
    public function deleteByIP(string $ip):void
    {
         $this->tx_service->transaction(function () use ($ip) {
            $banned_ip = $this->banned_ip_repository->getByIp($ip);
            if(is_null($banned_ip))
                throw new EntityNotFoundException();

            $this->banned_ip_repository->delete($banned_ip);
            $this->cache_service->delete($ip);
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
        // TODO: Implement create() method.
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
        // TODO: Implement update() method.
    }

    /**
     * @param int $id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function delete(int $id): void
    {
        // TODO: Implement delete() method.
    }
}