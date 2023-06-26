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

use Doctrine\ORM\Query\ResultSetMappingBuilder;
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
        Log::debug
        (
            sprintf
            (
                "DoctrineResourceServerRepository::getByAudienceAndIpAndActive audience %s ip %s",
                json_encode($audience),
                $ip
            )
        );

        $query = <<<SQL
SELECT r.* FROM oauth2_resource_server r
WHERE FIND_IN_SET('{$ip}', r.ips) AND r.active = 1
SQL;

        $hosts_query = "";

        foreach ($audience as $index => $audience_item) {
            if ($index > 0) {
                $hosts_query .= " OR ";
            }
            $hosts_query.= sprintf(" FIND_IN_SET('%s',r.host) ", $audience_item);
        }

        if(!empty($hosts_query))
             $hosts_query = " AND (". $hosts_query .")";

        Log::debug(sprintf("DoctrineResourceServerRepository::getByAudienceAndIpAndActive hosts_query %s", $hosts_query));

        $query = $query . $hosts_query. " LIMIT 1;";

        Log::debug(sprintf("DoctrineResourceServerRepository::getByAudienceAndIpAndActive query %s", $query));

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata($this->getBaseEntity(), 'r');

        // build rsm here
        $native_query = $this->getEntityManager()->createNativeQuery($query, $rsm);

        $res = $native_query->getResult();

        return count($res) > 0 ? $res[0] : null;
    }
}