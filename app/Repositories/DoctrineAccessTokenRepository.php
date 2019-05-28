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
/**
 * Class DoctrineAccessTokenRepository
 * @package App\Repositories
 */
class DoctrineAccessTokenRepository
    extends AbstractDoctrineOAuth2TokenRepository
    implements IAccessTokenRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return AccessToken::class;
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