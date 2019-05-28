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
use Models\OAuth2\Api;
use OAuth2\Repositories\IApiRepository;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineApiRepository
 * @package App\Repositories
 */
final class DoctrineApiRepository extends ModelDoctrineRepository
    implements IApiRepository
{


    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'               => 'e.name:json_string',
            'active'             => 'e.active:json_boolean',
            'resource_server_id' => new DoctrineLeftJoinFilterMapping("e.resource_server", "r" ,"r.id :operator :value")
        ];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return Api::class;
    }

    /**
     * @param string $api_name
     * @return Api
     */
    public function getByName(string $api_name):?Api
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("a")
            ->from($this->getBaseEntity(), "a")
            ->where("a.name in (:name)")
            ->setParameter("name", trim($api_name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $api_name
     * @param int $resource_server_id
     * @return Api
     */
    public function getByNameAndResourceServer(string $api_name, int $resource_server_id):?Api
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("a")
            ->from($this->getBaseEntity(), "a")
            ->innerJoin("a.resource_server", "r")
            ->where("a.name in (:name)")
            ->andWhere("r.id = (:resource_server_id)")
            ->setParameter("name", trim($api_name))
            ->setParameter("resource_server_id", $resource_server_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}