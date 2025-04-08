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
use Models\OAuth2\AccessToken;
use OAuth2\Repositories\IAccessTokenRepository;
use utils\DoctrineJoinFilterMapping;

/**
 * Class DoctrineAccessTokenRepository
 * @package App\Repositories
 */
class DoctrineAccessTokenRepository
    extends AbstractDoctrineOAuth2TokenRepository
    implements IAccessTokenRepository
{

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query): QueryBuilder
    {
        return $query->leftJoin("e.client", "c");
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return AccessToken::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        $res = parent::getFilterMappings();
        return array_merge($res, [
            'client_name'  => new DoctrineJoinFilterMapping
            (
                'e.client',
                'c',
                "c.app_name :operator :value"
            ),
            'device_info' => 'e.device_info:json_string',
            'from_ip' => 'e.from_ip:json_string',
            'scope' => 'e.scope:json_string',
        ]);
    }

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'client_name' => 'c.app_name',
            'created_at' => 'e.created_at',
            'device_info' => 'e.device_info',
            'from_ip' => 'e.from_ip',
            'scope' => 'e.scope',
        ];
    }

    /**
     * @param string $hashed_value
     * @return AccessToken|null
     */
    function getByValue(string $hashed_value):?AccessToken
    {
        return $this->findOneBy(['value' => $hashed_value]);
    }

    /**
     * @param string $hashed_value
     * @return AccessToken|null
     */
    function getByValueCacheable(string $hashed_value):?AccessToken
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.value = (:value)")
            ->setParameter("value", trim($hashed_value))
            ->setMaxResults(1)
            ->getQuery()
            ->setCacheable(true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $hashed_value
     * @return AccessToken|null
     */
    function getByAuthCode(string $hashed_value): ?AccessToken
    {
        return $this->findOneBy(['associated_authorization_code' => $hashed_value]);
    }

    /**
     * @param int $refresh_token_id
     * @return AccessToken[]
     */
    function getByRefreshToken(int $refresh_token_id):array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->join("e.refresh_token", "refresh_token")
            ->where("refresh_token.id = (:refresh_token_d)")
            ->setParameter("refresh_token_d", $refresh_token_id)->getQuery()->execute();
    }

}