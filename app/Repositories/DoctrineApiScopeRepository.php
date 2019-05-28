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
use Models\OAuth2\ApiScope;
use OAuth2\Repositories\IApiScopeRepository;
use utils\DoctrineFilterMapping;
use utils\DoctrineLeftJoinFilterMapping;

/**
 * Class DoctrineApiScopeRepository
 * @package App\Repositories
 */
class DoctrineApiScopeRepository
    extends ModelDoctrineRepository
    implements IApiScopeRepository
{

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'               => 'e.name:json_string',
            'is_assigned_by_groups'  => new DoctrineFilterMapping
            (
                " e.assigned_by_groups :operator :value"
            ),
            'api_id' => new DoctrineLeftJoinFilterMapping("e.api", "a" ,"a.id :operator :value")
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return ApiScope::class;
    }

    /**
     * @param array $scopes_names
     * @return ApiScope[]
     */
    public function getByNames(array $scopes_names): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(ApiScope::class, "s")
            ->where("s.active = 1")
            ->andWhere("s.name in (:scopes_names)")
            ->setParameter("scopes_names", $scopes_names)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $scope_name
     * @return ApiScope|null
     */
    public function getFirstByName(string $scope_name): ?ApiScope
    {

        return $this->findOneBy(
            [
                'active' => true,
                'name' => trim($scope_name)
            ]
        );
    }

    /**
     * @return ApiScope[]
     */
    public function getDefaults(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(ApiScope::class, "s")
            ->where("s.active = 1")
            ->andWhere("s.default = 1")
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ApiScope[]
     */
    public function getActives(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(ApiScope::class, "s")
            ->where("s.active = 1")
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ApiScope[]
     */
    public function getAssignableByGroups(): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s")
            ->from(ApiScope::class, "s")
            ->where("s.active = 1")
            ->andWhere("s.assigned_by_groups = 1")
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $scopes_names
     * @return string[]
     */
    public function getFriendlyScopesByName(array $scopes_names): array
    {
        $result =  $this->getEntityManager()
            ->createQueryBuilder()
            ->select("s.short_description")
            ->from(ApiScope::class, "s")
            ->where("s.active = 1")
            ->andWhere("s.name in (:scopes_names)")
            ->setParameter("scopes_names", $scopes_names)
            ->getQuery()
            ->getScalarResult();
        $res = [];
        foreach ($result as $item){
            $res[] = $item['short_description'];
        }
        return $res;
    }

    /**
     * Get all active scopes (system/non system ones)
     * @param bool $system
     * @param bool $assigned_by_groups
     * @return ApiScope[]
     */
    public function getAvailableScopes(bool $system = false, bool $assigned_by_groups = false): array
    {
        $res    = [];
        $scopes = $this->getActives();

        foreach ($scopes as $scope)
        {
            $api = $scope->getApi();
            if (!is_null($api) && $api->getResourceServer()->isActive() && $api->isActive()) {
                if ($scope->isSystem() && !$system) {
                    continue;
                }
                if ($scope->isAssignedByGroups() && !$assigned_by_groups) {
                    continue;
                }
                $res[] = $scope;
            }
        }

        return $res;
    }
}