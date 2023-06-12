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

use Illuminate\Support\Facades\Log;
use Models\OAuth2\ResourceServer;
use OAuth2\Repositories\IResourceServerRepository;
/**
 * Class DoctrineResourceServerRepository
 * @package App\Repositories
 */
class DoctrineResourceServerRepository
    extends ModelDoctrineRepository
    implements IResourceServerRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
       return ResourceServer::class;
    }

    /**
     * @param string|array $host
     * @return ResourceServer
     */
    public function getByHost(string $host):?ResourceServer
    {
        if(!is_array($host)) $host = [$host];

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("r")
            ->from($this->getBaseEntity(), "r")
            ->where("r.host in (:host)")
            ->setParameter("host", $host)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $ip
     * @return ResourceServer
     */
    public function getByIp(string $ip):?ResourceServer
    {
        Log::debug(sprintf("DoctrineResourceServerRepository::getByIp ip %s", $ip));
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("r")
            ->from($this->getBaseEntity(), "r")
            ->where("r.ips like :ip")
            ->setParameter("ip", '%'.trim($ip).'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $name
     * @return ResourceServer
     */
    public function getByFriendlyName(string $name):?ResourceServer
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("r")
            ->from($this->getBaseEntity(), "r")
            ->where("r.friendly_name = :friendly_name")
            ->setParameter("friendly_name", trim($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array $audience
     * @param string $ip
     * @return ResourceServer
     */
    public function getByAudienceAndIpAndActive(array $audience, string $ip):?ResourceServer
    {
        Log::debug(sprintf("DoctrineResourceServerRepository::getByAudienceAndIpAndActive audience %s ip %s", json_encode($audience), $ip));
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("r")
            ->from($this->getBaseEntity(), "r")
            ->where("r.ips like :ip ")
            ->andWhere("r.host in (:host)")
            ->andWhere("r.active = 1")
            ->setParameter("ip", '%'.trim($ip).'%')
            ->setParameter("host", $audience)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}