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
use App\Models\OAuth2\Factories\ResourceServerFactory;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Models\OAuth2\ResourceServer;
use models\utils\IEntity;
use OAuth2\Models\IClient;
use OAuth2\Repositories\IClientRepository;
use OAuth2\Repositories\IResourceServerRepository;
use OAuth2\Services\IClientService;
use OAuth2\Services\IResourceServerService;
use Utils\Db\ITransactionService;
/**
 * Class ResourceServerService
 * @package Services\OAuth2
 */
final class ResourceServerService implements IResourceServerService
{

    /**
     * @var IClientService
     */
    private $client_service;

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var IResourceServerRepository
     */
    private $repository;

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * ResourceServerService constructor.
     * @param IClientService $client_service
     * @param IClientRepository $client_repository
     * @param IResourceServerRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IClientService $client_service,
        IClientRepository $client_repository,
        IResourceServerRepository $repository,
        ITransactionService $tx_service
    )
    {
        $this->client_service = $client_service;
        $this->repository = $repository;
        $this->client_repository = $client_repository;
        $this->tx_service = $tx_service;
    }

    /**
     * @param array $payload
     * @return IEntity
     * @throws \Exception
     */
    public function create(array $payload): IEntity
    {

        return $this->tx_service->transaction(function () use ($payload) {

            $host = trim($payload['host']);
            $friendly_name = trim($payload['friendly_name']);
            if ($this->repository->getByHost($host) != null) {
                throw new ValidationException
                (
                    sprintf('there is already another resource server with that hostname (%s).', $host)
                );
            }

            if ($this->repository->getByFriendlyName($friendly_name) != null) {
                throw new ValidationException
                (
                    sprintf('there is already another resource server with that friendly name (%s).', $friendly_name)
                );
            }

            $resource_server = ResourceServerFactory::build($payload);

            // creates a new client for this brand new resource server
            $resource_server_client = $this->client_service->create
            (
                [
                    'application_type' => IClient::ApplicationType_Service,
                    'app_name' => $host . '.confidential.application',
                    'app_description' => $friendly_name . ' confidential oauth2 application'
                ]
            );


            $resource_server->setClient($resource_server_client);
            // does not expires ...
            $resource_server_client->setClientSecretNoExpiration();

            $this->repository->add($resource_server);

            return $resource_server;
        });

    }

    /**
     * @param int $id
     * @param array $payload
     * @return IEntity
     * @throws \Exception
     */
    public function update(int $id, array $payload): IEntity
    {

        return $this->tx_service->transaction(function () use ($id, $payload) {

            $resource_server = $this->repository->getById($id);
            if (is_null($resource_server) || !$resource_server instanceof ResourceServer) {
                throw new EntityNotFoundException(sprintf('resource server id %s does not exists!', $id));
            }

            if (isset($payload['host'])) {
                $host = trim($payload['host']);
                $former_resource_server = $this->repository->getByHost($host);
                if (!is_null($former_resource_server) && $former_resource_server->getId() != $resource_server->getId()) {
                    throw new ValidationException
                    (
                        sprintf('there is already another resource server with that hostname (%s).', $host)
                    );
                }
            }

            if (isset($payload['friendly_name'])) {
                $friendly_name = trim($payload['friendly_name']);
                $former_resource_server = $this->repository->getByFriendlyName($friendly_name);
                if (!is_null($former_resource_server) && $former_resource_server->getId() != $resource_server->getId()) {
                    throw new ValidationException
                    (
                        sprintf('there is already another resource server with that friendly name (%s).', $friendly_name)
                    );
                }
            }

            return ResourceServerFactory::populate($resource_server, $payload);

        });
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {

        $this->tx_service->transaction(function () use ($id) {

            $resource_server = $this->repository->getById($id);

            if (is_null($resource_server)) {
                throw new EntityNotFoundException(sprintf('resource server id %s does not exists!', $id));
            }
            $this->repository->delete($resource_server);
        });
    }

    /**
     * @param int $id
     * @return ResourceServer
     * @throws EntityNotFoundException
     */
    public function regenerateClientSecret(int $id): ResourceServer
    {

        return $this->tx_service->transaction(function () use ($id) {

            $resource_server = $this->repository->getById($id);

            if (is_null($resource_server) || !$resource_server instanceof ResourceServer) {
                throw new EntityNotFoundException(sprintf('resource server id %s does not exists!', $id));
            }

            if (!$resource_server->hasClient())
                throw new EntityNotFoundException(sprintf('client not found for resource server id %s!', $id));

            $client = $resource_server->getClient();
            $this->client_service->regenerateClientSecret($client->getId());

            return $resource_server;

        });
    }

}
