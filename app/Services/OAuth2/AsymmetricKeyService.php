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
use models\exceptions\EntityNotFoundException;
use models\utils\IEntity;
use Utils\Db\ITransactionService;
use OAuth2\Repositories\IAsymmetricKeyRepository;
/**
 * Class AsymmetricKeyService
 * @package Services\OAuth2
 */
abstract class AsymmetricKeyService extends AbstractService
{

    /**
     * @var IAsymmetricKeyRepository
     */
    protected $repository;

    public function __construct(
        IAsymmetricKeyRepository $repository,
        ITransactionService $tx_service)
    {
        parent::__construct($tx_service);
        $this->repository        = $repository;
    }

    /**
     * @param array $params
     * @return IEntity
     */
    abstract public function create(array $params):IEntity;

    /**
     * @param $key_id
     * @param array $params
     * @throws EntityNotFoundException
     * @return IEntity
     */
    public function update(int $key_id, array $params): IEntity {

        return $this->tx_service->transaction(function() use($key_id, $params)
        {

            $key = $this->repository->getById($key_id);
            if(is_null($key))
                throw new EntityNotFoundException();

            if(isset($params['active'])){
                $key->setActive($params['active']);
            }

            return $key;
        });
    }

    /**
     * @param int $id
     * @throws EntityNotFoundException
     */
    public function delete(int $id):void
    {

        $this->tx_service->transaction(function() use($id)
        {

            $key = $this->repository->getById($id);
            if(is_null($key))
                throw new EntityNotFoundException();

            $this->repository->delete($key);
        });
    }
}