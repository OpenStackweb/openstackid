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
use App\libs\OAuth2\Repositories\IOAuth2OTPRepository;
use App\libs\Utils\PunnyCodeHelper;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
/**
 * Class DoctrineOAuth2OTPRepository
 * @package App\Repositories
 */
class DoctrineOAuth2OTPRepository
    extends ModelDoctrineRepository
    implements IOAuth2OTPRepository
{

    protected function getBaseEntity(): string
    {
       return OAuth2OTP::class;
    }

    public function getByValue(string $value): ?OAuth2OTP
    {
        return $this->findOneBy(['value' => trim($value)]);
    }

    /**
     * @param string $connection
     * @param string $user_name
     * @param Client|null $client
     * @return OAuth2OTP|null
     */
    public function getLatestByConnectionAndUserNameNotRedeemed
    (
        string $connection,
        string $user_name,
        ?Client $client
    ):?OAuth2OTP
    {
        $user_name = PunnyCodeHelper::encodeEmail($user_name);

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.connection = (:connection)")
            ->andWhere("(e.email = (:user_name) or e.phone_number = (:user_name))")
            ->andWhere("e.redeemed_at is null")
            ->setParameter("connection", $connection)
            ->setParameter("user_name", $user_name);
        // add client id condition
        if(!is_null($client)){
            $query->join("e.client", "c")->andWhere("c.id = :client_id")
                ->setParameter("client_id", $client->getId());
        }
        // try to get the latest one
        $query->addOrderBy("e.id", "DESC");
        return $query->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @param string $user_name
     * @param Client|null $client
     * @return OAuth2OTP[]
     */
    public function getByUserNameNotRedeemed
    (
        string $user_name,
        ?Client $client = null
    )
    {
        $user_name = PunnyCodeHelper::encodeEmail($user_name);

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->andWhere("(e.email = (:user_name) or e.phone_number = (:user_name))")
            ->andWhere("e.redeemed_at is null")
            ->setParameter("user_name", $user_name);
        // add client id condition
        if(!is_null($client)){
            $query->join("e.client", "c")->andWhere("c.id = :client_id")
                ->setParameter("client_id", $client->getId());
        }
        $query->addOrderBy("e.id", "DESC");
        return $query->getQuery()->getResult();
    }
}