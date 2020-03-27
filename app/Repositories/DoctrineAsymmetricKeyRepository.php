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
use Models\OAuth2\AsymmetricKey;
use OAuth2\Repositories\IAsymmetricKeyRepository;
/**
 * Class DoctrineAsymmetricKeyRepository
 * @package App\Repositories
 */
abstract class DoctrineAsymmetricKeyRepository
    extends ModelDoctrineRepository implements IAsymmetricKeyRepository
{

    /**
     * @param string $pem
     * @return AsymmetricKey|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByPEM(string $pem):?AsymmetricKey
    {
        try {
            return $this->getEntityManager()
                ->createQueryBuilder()
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->where("e.pem_content = (:pem)")
                ->setParameter("pem", trim($pem))
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }
        catch (\Exception $ex){
            return null;
        }
    }

    /**
     * @param string $type
     * @param string $usage
     * @params string $alg
     * @param \DateTime $valid_from
     * @param \DateTime $valid_to
     * @param int|null $owner_id
     * @return AsymmetricKey[]
     */
    public function getByValidityRange($type, $usage, $alg, \DateTime $valid_from, \DateTime $valid_to, $owner_id = null):array
    {
        // (StartA <= EndB)  and  (EndA >= StartB)
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.type = (:type)")
            ->andWhere("e.usage = (:usage)")
            ->andWhere("e.alg = (:alg)")
            ->andWhere("e.valid_from <= (:valid_to)")
            ->andWhere("e.valid_to >= (:valid_from)")
            ->andWhere("e.active = 1")
            ->setParameter("usage", $usage )
            ->setParameter("type", $type )
            ->setParameter("alg", $alg )
            ->setParameter("valid_to", $valid_to)
            ->setParameter("valid_from", $valid_from);
        ;
        if(!is_null($owner_id))
        {
            $query = $query->andWhere('e.oauth2_client.id = (:owner_id)');
            $query->setParameter("owner_id", $owner_id);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return AsymmetricKey[]
     */
    public function getActives():array
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where('e.valid_from <= :now1')
            ->andWhere('e.valid_to >= :now2')
            ->andWhere("e.active = 1")
            ->setParameters([
                'now1' => $now,
                'now2' => $now,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $type
     * @param string $usage
     * @param string $alg
     * @param int|null $owner_id
     * @return AsymmetricKey|null
     */
    public function getActiveByCriteria(string $type, string $usage, string $alg, int $owner_id = null): ?AsymmetricKey
    {
        try {
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("e")
                ->from($this->getBaseEntity(), "e")
                ->where("e.type = (:type)")
                ->andWhere("e.usage = (:usage)")
                ->andWhere("e.alg = (:alg)")
                ->andWhere("e.valid_from <= (:valid_to)")
                ->andWhere("e.valid_to >= (:valid_from)")
                ->andWhere("e.active = 1")
                ->setParameter("usage", $usage)
                ->setParameter("type", $type)
                ->setParameter("alg", $alg)
                ->setParameter("valid_to", $now)
                ->setParameter("valid_from", $now);;
            if (!is_null($owner_id)) {
                $query = $query->andWhere('e.oauth2_client.id = (:owner_id)');
                $query->setParameter("owner_id", $owner_id);
            }

            return $query->getQuery()->getOneOrNullResult();
        }
        catch (\Exception $ex){
            return null;
        }
    }
}