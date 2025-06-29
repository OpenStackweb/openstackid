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

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
/**
 * Class DoctrineRepository
 * @package App\Repositories
 */
abstract class DoctrineRepository extends EntityRepository implements IBaseRepository
{

    /**
     * @var string
     */
    protected $manager_name;
    /**
     * @return EntityManager
     */
    protected function getEntityManager(): \Doctrine\ORM\EntityManagerInterface
    {
        return Registry::getManager($this->manager_name);
    }

    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * @param int $id
     * @return IEntity|null|object
     */
    public function getByIdExclusiveLock($id){
        return $this->find($id, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @param $entity
     * @param bool $sync
     * @return mixed|void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function add($entity, $sync = false)
    {
        $this->getEntityManager()->persist($entity);
        if($sync)
            $this->getEntityManager()->flush($entity);
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    public function delete($entity)
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @return IEntity[]
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /**
     * @return string
     */
    protected abstract function getBaseEntity();

    /**
     * @return array
     */
    protected abstract function getFilterMappings();

    /**
     * @return array
     */
    protected abstract function getOrderMappings();

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected abstract function applyExtraFilters(QueryBuilder $query);

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected abstract function applyExtraJoins(QueryBuilder $query);

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null){

        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        $query = $this->applyExtraFilters($query);

        $query = $this->applyExtraJoins($query);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings());
        }

        if(!is_null($order)){
            $order->apply2Query($query, $this->getOrderMappings());
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = array();

        foreach($paginator as $entity)
            array_push($data, $entity);

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select($alias)
            ->from($this->getEntityName(), $alias, $indexBy);
    }

    /**
     * Creates a new result set mapping builder for this entity.
     *
     * The column naming strategy is "INCREMENT".
     *
     * @param string $alias
     *
     * @return Query\ResultSetMappingBuilder
     */
    public function createResultSetMappingBuilder($alias): Query\ResultSetMappingBuilder
    {
        $rsm = new Query\ResultSetMappingBuilder($this->getEntityManager(), ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->getEntityName(), $alias);

        return $rsm;
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->getEntityManager()->clear($this->getClassMetadata()->rootEntityName);
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param mixed    $id          The identifier.
     * @param int|null $lockMode    One of the \Doctrine\DBAL\LockMode::* constants
     *                              or NULL if no specific lock mode should be used
     *                              during the search.
     * @param int|null $lockVersion The lock version.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        return $this->getEntityManager()->find($this->getEntityName(), $id, $lockMode, $lockVersion);
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->getEntityName());
        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = null): ?object
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->getEntityName());

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @todo Add this method to `ObjectRepository` interface in the next major release
     *
     * @param array $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     */
    public function count(array $criteria = []): int
    {
        return $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->getEntityName())->count($criteria);
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @param Criteria $criteria
     *
     * @return AbstractLazyCollection&Selectable
     */
    public function matching(Criteria $criteria): AbstractLazyCollection&Selectable
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->getEntityName());
        return new LazyCriteriaCollection($persister, $criteria);
    }
}