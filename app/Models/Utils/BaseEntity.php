<?php namespace App\Models\Utils;
/**
 * Copyright 2017 OpenStack Foundation
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

use Doctrine\ORM\Event\PreUpdateEventArgs;
use models\utils\IEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use LaravelDoctrine\ORM\Facades\Registry;
/***
 * Class BaseEntity
 * @package App\Models\Utils
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
class BaseEntity implements IEntity
{
    const DefaultTimeZone = 'UTC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer', unique: true, nullable: false)]
    protected $id;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected $created_at;

    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    protected $updated_at;

    /**
     * @return int
     */
    public function getIdentifier()
    {
        return (int)$this->id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getIdentifier();
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     */
    public function setCreatedAt(\DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    /**
     * @param \DateTime $updated_at
     */
    public function setUpdatedAt(\DateTime $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    public function __construct()
    {
        $this->id = 0;
        $now = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
        $this->created_at = $now;
        $this->updated_at = $now;
    }

    /**
     * @return QueryBuilder
     */
    protected function createQueryBuilder(){
        return Registry::getManager(self::EntityManager)->createQueryBuilder();
    }

    /**
     * @param string $dql
     * @return Query
     */
    protected function createQuery($dql){
        return Registry::getManager(self::EntityManager)->createQuery($dql);
    }

    /**
     * @param string $sql
     * @return mixed
     */
    protected function prepareRawSQL($sql){
        return Registry::getManager(self::EntityManager)->getConnection()->prepare($sql);
    }

    /**
     * @param string $sql
     * @return mixed
     */
    protected static function prepareRawSQLStatic($sql){
        return Registry::getManager(self::EntityManager)->getConnection()->prepare($sql);
    }

    /**
     * @return EntityManager
     */
    protected function getEM(){
        return Registry::getManager(self::EntityManager);
    }

    /**
     * @return EntityManager
     */
    protected static function getEMStatic(){
        return Registry::getManager(self::EntityManager);
    }

    const EntityManager = 'model';

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->id == 0;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

    #[ORM\PreUpdate] // :
    public function updating(PreUpdateEventArgs $args)
    {
        $now = new \DateTime('now', new \DateTimeZone(self::DefaultTimeZone));
        $this->updated_at = $now;
    }
}