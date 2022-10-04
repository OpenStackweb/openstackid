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
use Models\OAuth2\RefreshToken;
use OAuth2\Repositories\IRefreshTokenRepository;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;

/**
 * Class DoctrineRefreshTokenRepository
 * @package App\Repositories
 */
class DoctrineRefreshTokenRepository extends AbstractDoctrineOAuth2TokenRepository implements IRefreshTokenRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return RefreshToken::class;
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'owner_id'  => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'owner',
                "owner.id  :operator :value"
            ),
            'client_id' => new DoctrineJoinFilterMapping
            (
                'e.client',
                'client',
                "client.id :operator :value"
            ),
            'is_valid'  => new DoctrineFilterMapping(
                "(e.lifetime = 0 AND e.void = false) OR DATEADD(e.created_at, e.lifetime, 'SECOND') >= UTC_TIMESTAMP()"
            )
        ];
    }

    /**
     * @param string $hashed_value
     * @return RefreshToken|null
     */
    function getByValue(string $hashed_value):?RefreshToken
    {
        return $this->findOneBy(['value' => $hashed_value]);
    }

    /**
     * @param string $hashed_value
     * @return RefreshToken|null
     */
    function getByValueCacheable(string $hashed_value):?RefreshToken
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
}