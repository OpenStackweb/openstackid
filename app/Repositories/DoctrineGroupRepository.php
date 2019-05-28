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
use App\libs\Auth\Repositories\IGroupRepository;
use Auth\Group;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Class DoctrineGroupRepository
 * @package App\Repositories
 */
final class DoctrineGroupRepository extends ModelDoctrineRepository implements IGroupRepository
{
    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'name'    => 'e.name:json_string',
            'slug'    => 'e.slug:json_string',
            'active'  => 'e.active:json_boolean',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return Group::class;
    }

    /**
     * @return Group[]
     */
    public function getDefaultOnes(): array
    {
        return $this->findBy([
            'default' => true
        ]);
    }

    /**
     * @param string $name
     * @return Group|null
     */
    public function getOneByName(string $name): ?Group
    {
        return $this->findOneBy([
            'name' => trim($name)
        ]);
    }

    /**
     * @param string $slug
     * @return Group|null
     */
    public function getOneBySlug(string $slug): ?Group
    {
        return $this->findOneBy([
            'slug' => trim($slug)
        ]);
    }
}