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
use Models\OAuth2\ApiEndpoint;
use OAuth2\Repositories\IApiEndpointRepository;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineApiEndpointRepository
 * @package App\Repositories
 */
class DoctrineApiEndpointRepository
    extends ModelDoctrineRepository implements IApiEndpointRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'        => 'e.name:json_string',
            'http_method' => 'e.http_method:json_string',
            'route'       => 'e.route:json_string',
            'active'      => 'e.active|json_boolean',
            'api_id'      => new DoctrineLeftJoinFilterMapping("e.api", "a" ,"a.id :operator :value")
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return ApiEndpoint::class;
    }

    /**
     * @param string $url
     * @param string $http_method
     * @return ApiEndpoint|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getApiEndpointByUrlAndMethod(string $url, string $http_method): ?ApiEndpoint
    {
        return $this->getApiEndpointByUrlAndMethodAndApi($url, $http_method);
    }

    /**
     * @param string $url
     * @param string|null $http_method
     * @param Api $api
     * @return ApiEndpoint|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getApiEndpointByUrlAndMethodAndApi(string $url, string $http_method = null, Api $api = null): ?ApiEndpoint
    {
        $query =  $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->innerJoin("e.api", "a");

        if(!empty($url)){
            $query = $query->andWhere("e.route = :url")->setParameter("url", $url);
        }

        if(!empty($http_method)){
            $query = $query->andWhere("e.http_method = :http_method")->setParameter("http_method", $http_method);
        }

        if(!is_null($api)){
            $query = $query->andWhere("a.id = :api_id")->setParameter("api_id", $api->getId());
        }

        $res =  $query
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $res;
    }

    /**
     * @param string $url
     * @return ApiEndpoint|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getApiEndpointByUrl(string $url): ?ApiEndpoint
    {
        $this->getApiEndpointByUrlAndMethodAndApi($url);
    }
}