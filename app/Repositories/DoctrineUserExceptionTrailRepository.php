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
use App\libs\Auth\Repositories\IUserExceptionTrailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Models\UserExceptionTrail;
/**
 * Class DoctrineUserExceptionTrailRepository
 * @package App\Repositories
 */
class DoctrineUserExceptionTrailRepository
    extends ModelDoctrineRepository
    implements IUserExceptionTrailRepository
{
    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return UserExceptionTrail::class;
    }

    /**
     * @param string $ip
     * @param string $type
     * @param int $minutes_without_ex
     * @return int
     */
    public function getCountByIPTypeOfLatestUserExceptions(string $ip, string $type, int $minutes_without_ex): int
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("count(e.id)")
            ->from($this->getBaseEntity(), "e")
            ->where("e.from_ip = :ip")
            ->andWhere("e.exception_type = :exception_type")
            ->andWhere("DATESUB(UTC_TIMESTAMP(), :minutes, 'MINUTE') < e.created_at")
            ->setParameters(new ArrayCollection(
                    [
                        new Parameter('ip', trim($ip)),
                        new Parameter('exception_type', trim($type)),
                        new Parameter('minutes', $minutes_without_ex)
                    ]
                )
            )
            ->getQuery()
            ->getSingleScalarResult();
    }
}