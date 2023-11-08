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
use models\exceptions\EntityNotFoundException;
use Models\OAuth2\AsymmetricKey;
use Models\OAuth2\Client;
use models\utils\IEntity;
use OAuth2\Models\IAsymmetricKey;
use OAuth2\Services\IClientPublicKeyService;
use Utils\Db\ITransactionService;
use OAuth2\Repositories\IClientPublicKeyRepository;
use Models\OAuth2\ClientPublicKey;
use OAuth2\Repositories\IClientRepository;
use models\exceptions\ValidationException;
use Utils\Services\IAuthService;
use DateTime;
/**
 * Class ClientPublicKeyService
 * @package Services\OAuth2
 */
final class ClientPublicKeyService extends AsymmetricKeyService implements IClientPublicKeyService
{

    /**
     * @var IClientRepository
     */
    private $client_repository;

    /**
     * @var IAuthService
     */
    private $auth_service;

    /**
     * ClientPublicKeyService constructor.
     * @param IClientPublicKeyRepository $repository
     * @param IClientRepository $client_repository
     * @param IAuthService $auth_service
     * @param ITransactionService $tx_service
     */
    public function __construct(
        IClientPublicKeyRepository $repository,
        IClientRepository          $client_repository,
        IAuthService               $auth_service,
        ITransactionService        $tx_service
    )
    {
        parent::__construct($repository, $tx_service);

        $this->client_repository = $client_repository;
        $this->auth_service      = $auth_service;

    }

    /**
     * @param array $params
     * @return IAsymmetricKey
     */
    public function create(array $params):IEntity
    {

        return $this->tx_service->transaction(function() use($params)
        {

            if ($this->repository->getByPEM($params['pem_content']))
            {
                throw new ValidationException('public key already exists on another client, choose another one!.');
            }

            $client = $this->client_repository->getById(intval($params['owner_id']));

            if(is_null($client) || !$client instanceof Client)
                throw new EntityNotFoundException('client does not exits!');

            $existent_kid = $client->getPublicKeyByIdentifier(trim($params['kid']));

            if ($existent_kid)
            {
                throw new ValidationException('public key identifier (kid) already exists!.');
            }

            $old_key_active = $client->getCurrentPublicKeyByTypeUseAlgAndRange(
                trim($params['type']),
                trim($params['usage']),
                trim($params['usage']),
                new DateTime($params['valid_to']),
                new DateTime($params['valid_from'])
            );

            $public_key = ClientPublicKey::buildFromPEM
            (
                $params['kid'],
                $params['type'],
                $params['usage'],
                $params['pem_content'],
                $params['alg'],
                $old_key_active ? false : $params['active'],
                new DateTime($params['valid_from']),
                new DateTime($params['valid_to'])
            );

            $client->addPublicKey($public_key);
            $client->setEditedBy($this->auth_service->getCurrentUser());

            return $public_key;
        });
    }

}