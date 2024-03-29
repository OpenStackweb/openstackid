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

use App\libs\Utils\PunnyCodeHelper;
use Auth\Repositories\IUserRepository;
use Auth\User;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
/**
 * Class DoctrineUserRepository
 * @package App\Repositories
 */
final class DoctrineUserRepository
    extends ModelDoctrineRepository implements IUserRepository
{

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'first_name' => 'e.first_name',
            'last_name' => 'e.last_name',
            'email' => 'e.email',
            'active' => 'e.active',
            'identifier' => 'e.identifier',
            'last_login_date' => 'e.last_login_date',
            'spam_type' => 'e.spam_type',
        ];
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'first_name'  => 'e.first_name:json_string',
            'last_name'   => 'e.last_name:json_string',
            'full_name'   => new DoctrineFilterMapping("concat(e.first_name, ' ', e.last_name) :operator :value"),
            'github_user' => 'e.github_user:json_string',
            'email'       => ['e.email:json_email', 'e.second_email:json_email', 'e.third_email:json_email'],
            'primary_email' => 'e.email:json_email',
            'active'      => 'e.active:json_boolean',
            'group_id'    => new DoctrineJoinFilterMapping('e.groups', "g", "g.id :operator :value")
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return User::class;
    }

    /**
     * @param mixed $identifier
     * @param string $token
     * @return User|null
     */
    public function getByToken(string $token): ?User
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->Where("e.remember_token = (:token)")
            ->setParameter("token", trim($token))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $term
     * @return User|null
     */
    public function getByEmailOrName(string $term): ?User
    {
        $term = PunnyCodeHelper::encodeEmail($term);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->Where("e.email = (:term)")
            ->setParameter("term", $term)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $user_identifier
     * @return User|null
     */
    public function getByIdentifier($user_identifier): ?User
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->Where("e.identifier = (:identifier)")
            ->setParameter("identifier", trim($user_identifier))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $token
     * @return User|null
     */
    public function getByVerificationEmailToken(string $token): ?User
    {
        return $this->findOneBy([
            'email_verified_token_hash' => User::createConfirmationTokenHash($token)
        ]);
    }
}