<?php namespace App\Repositories;
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

use Doctrine\ORM\QueryBuilder;
use Models\OAuth2\Client;
use OAuth2\Repositories\IClientRepository;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineOAuth2ClientRepository
 * @package App\Repositories
 */
final class DoctrineOAuth2ClientRepository
    extends ModelDoctrineRepository
    implements IClientRepository
{


    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'user_id' => [
               "owner.id :operator :value",
                "admin_user.id :operator :value"
            ],
            'locked' =>  'e.locked',
            'client_id' =>  'e.client_id',
            'resource_server_not_set' => new DoctrineLeftJoinFilterMapping("e.resource_server", "resource_server", "resource_server is null"),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query)
    {
        $query = $query
            ->leftJoin("e.user", "owner")
            ->leftJoin("e.admin_users", "admin_user");
        return $query;
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return Client::class;
    }

    /**
     * @param string $app_name
     * @return Client|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByApplicationName(string $app_name):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.app_name = (:app_name)")
            ->setParameter("app_name", trim($app_name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $client_id
     * @return Client|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getClientById(string $client_id):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from($this->getBaseEntity(), "c")
            ->where("c.client_id = (:client_id)")
            ->setParameter("client_id", trim($client_id))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @return Client|null
     */
    public function getClientByIdentifier(int $id):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from($this->getBaseEntity(), "c")
            ->where("c.id = (:id)")
            ->setParameter("id", intval($id))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $origin
     * @return Client|null
     */
    public function getByOrigin(string $origin):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from($this->getBaseEntity(), "c")
            ->where("c.allowed_origins like :origin")
            ->setParameter("origin", '%'.trim($origin).'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @param string $custom_scheme
     * @return bool
     */
    public function hasCustomSchemeRegisteredForRedirectUrisOnAnotherClientThan(int $id, string $custom_scheme): bool
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("count(e.id)")
            ->from($this->getBaseEntity(), "e")
            ->where("e.redirect_uris like :custom_scheme")
            ->andWhere("e.id <> :id")
            ->setParameter("custom_scheme", '%' . trim($custom_scheme). '://%')
            ->setParameter("id", $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}