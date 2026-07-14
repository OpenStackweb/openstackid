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

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Models\OAuth2\Client;
use OAuth2\Repositories\IClientRepository;
use utils\DoctrineLeftJoinFilterMapping;
/**
 * Class DoctrineOAuth2ClientRepository
 * @package App\Repositories
 */
final class DoctrineOAuth2ClientRepository
    extends ModelDoctrineRepository
    implements IClientRepository
{


    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'user_id' => [
               "owner.id :operator :value",
                "admin_user.id :operator :value"
            ],
            'locked' =>  'e.locked',
            'client_id' =>  'e.client_id',
            'resource_server_not_set' => new DoctrineLeftJoinFilterMapping("e.resource_server", "resource_server", "resource_server is null"),
        ];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query)
    {
        $query = $query
            ->leftJoin("e.user", "owner")
            ->leftJoin("e.admin_users", "admin_user");
        return $query;
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return Client::class;
    }

    /**
     * @param string $app_name
     * @return Client|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByApplicationName(string $app_name):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e")
            ->where("e.app_name = (:app_name)")
            ->setParameter("app_name", trim($app_name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $client_id
     * @return Client|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getClientById(string $client_id):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from($this->getBaseEntity(), "c")
            ->where("c.client_id = (:client_id)")
            ->setParameter("client_id", trim($client_id))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $client_id
     * @return Client|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getClientByIdCacheable(string $client_id, bool $withResourceServer = true):?Client
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from($this->getBaseEntity(), 'c')
            ->where('c.client_id = :client_id')
            ->setParameter('client_id', trim($client_id))
            ->setMaxResults(1);

        if ($withResourceServer) {

            $qb->addSelect('rs')
                ->leftJoin('c.resource_server', 'rs');
        }

        $q = $qb->getQuery();

        $q->useQueryCache(true);
        $q->enableResultCache(600, 'client_by_id_'.md5($client_id)); // TTL 10 min
        $q->setHint(Query::HINT_READ_ONLY, true);

        return $q->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @return Client|null
     */
    public function getClientByIdentifier(int $id):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from($this->getBaseEntity(), "c")
            ->where("c.id = (:id)")
            ->setParameter("id", intval($id))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string $origin
     * @return Client|null
     */
    public function getByOrigin(string $origin):?Client
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("c")
            ->from($this->getBaseEntity(), "c")
            ->where("c.allowed_origins like :origin")
            ->setParameter("origin", '%'.trim($origin).'%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Interception-prevention rule checked across all three URI-bearing fields (redirect_uris,
     * post_logout_redirect_uris, allowed_origins): whichever field a scheme was first claimed in, another
     * client re-registering it in ANY of the three fields creates the same OS-level scheme-collision risk
     * (the OS routes a custom-scheme redirect to whichever installed app claims it, regardless of which
     * field of which client this server thinks it belongs to).
     *
     * @param int $id
     * @param string $custom_scheme
     * @return bool
     */
    public function hasCustomSchemeRegisteredOnAnotherClientThan(int $id, string $custom_scheme): bool
    {
        $scheme = trim($custom_scheme);
        // fields are comma-separated URI lists; a plain '%scheme://%' substring match false-positives on any
        // longer scheme ending in this one (e.g. 'roipapp' matching inside 'androipapp://...'). Anchor the
        // match to a real list-item boundary: the scheme starts the field, or immediately follows a comma.
        $starts_with = $scheme . '://%';
        $after_comma = '%,' . $scheme . '://%';

        $qb = $this->getEntityManager()->createQueryBuilder();
        $matches_field = function (string $field) use ($qb) {
            return $qb->expr()->orX(
                $qb->expr()->like($field, ':starts_with'),
                $qb->expr()->like($field, ':after_comma')
            );
        };

        return $qb
            ->select("count(e.id)")
            ->from($this->getBaseEntity(), "e")
            ->where($qb->expr()->orX(
                $matches_field("e.redirect_uris"),
                $matches_field("e.post_logout_redirect_uris"),
                $matches_field("e.allowed_origins")
            ))
            ->andWhere("e.id <> :id")
            ->setParameter("starts_with", $starts_with)
            ->setParameter("after_comma", $after_comma)
            ->setParameter("id", $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}