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
use App\libs\OAuth2\Repositories\IOAuth2TrailExceptionRepository;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2TrailException;
/**
 * Class DoctrineOAuth2TrailExceptionRepository
 * @package App\Repositories
 */
final class DoctrineOAuth2TrailExceptionRepository extends ModelDoctrineRepository
implements IOAuth2TrailExceptionRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return OAuth2TrailException::class;
    }

    /**
     * @param Client $client
     * @param string $type
     * @param int $minutes_without_ex
     * @return int
     */
    public function getCountByIPTypeOfLatestUserExceptions(Client $client, string $type, int $minutes_without_ex): int
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("count(e.id)")
            ->from($this->getBaseEntity(), "e")
            ->join("e.client", "client")
            ->where("client.id = :client_id")
            ->andWhere("e.exception_type = :exception_type")
            ->andWhere("DATESUB(UTC_TIMESTAMP(), :minutes, 'MINUTE') < e.created_at")
            ->setParameters(
                [
                    'client_id' => $client->getId(),
                    'exception_type' => trim($type),
                    'minutes' => $minutes_without_ex,
                ]
            )
            ->getQuery()->getSingleScalarResult();
    }
}