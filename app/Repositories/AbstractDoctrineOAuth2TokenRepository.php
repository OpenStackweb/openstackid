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
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;
use utils\DoctrineFilterMapping;
use utils\DoctrineJoinFilterMapping;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class AbstractDoctrineOAuth2TokenRepository
 * @package App\Repositories
 */
abstract class AbstractDoctrineOAuth2TokenRepository
    extends ModelDoctrineRepository
{
    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'owner_id'  => new DoctrineJoinFilterMapping
            (
                'e.owner',
                'owner',
                "owner.id  :operator :value"
            ),
            'client_id' => new DoctrineJoinFilterMapping
            (
                'e.client',
                'client',
                "client.id  :operator :value"
            ),
            'is_valid'  => new DoctrineFilterMapping(
                " DATEADD(e.created_at, e.lifetime, 'SECOND') >= UTC_TIMESTAMP()"
            )
        ];
    }

    /**
     * @param int $client_identifier
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllByClientIdentifier(int $client_identifier, PagingInfo $paging_info): PagingResponse
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual("client_id", $client_identifier));
        return $this->getAllByPage($paging_info, $filter);
    }

    /**
     * @param int $client_identifier
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllValidByClientIdentifier(int $client_identifier, PagingInfo $paging_info): PagingResponse
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual("client_id", $client_identifier));
        $filter->addFilterCondition(FilterElement::makeEqual("is_valid", true));
        return $this->getAllByPage($paging_info, $filter);
    }

    /**
     * @param int $user_id
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllByUserId(int $user_id, PagingInfo $paging_info): PagingResponse
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual("owner_id", $user_id));
        return $this->getAllByPage($paging_info, $filter);
    }

    /**
     * @param int $user_id
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getAllValidByUserId(int $user_id, PagingInfo $paging_info): PagingResponse
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual("owner_id", $user_id));
        $filter->addFilterCondition(FilterElement::makeEqual("is_valid", true));
        return $this->getAllByPage($paging_info, $filter);
    }

    /**
     * @param int $user_id
     * @param int $client_identifier
     * @param PagingInfo $paging_info
     * @return PagingResponse
     */
    function getValidCountByUserIdAndClientIdentifier(int $user_id, int $client_identifier): int
    {
        try {
            $filter = new Filter();
            $filter->addFilterCondition(FilterElement::makeEqual("owner_id", $user_id));
            $filter->addFilterCondition(FilterElement::makeEqual("client_id", $client_identifier));
            $filter->addFilterCondition(FilterElement::makeEqual("is_valid", true));

            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("count(e.id)")
                ->from($this->getBaseEntity(), "e");

            $query = $this->applyExtraFilters($query);

            $query = $this->applyExtraJoins($query);

            if (!is_null($filter)) {
                $filter->apply2Query($query, $this->getFilterMappings());
            }

            return (int)$query->getQuery()->getSingleScalarResult();
        }
        catch (\Exception $ex){
            Log::error($ex);
            return 0;
        }
    }

}