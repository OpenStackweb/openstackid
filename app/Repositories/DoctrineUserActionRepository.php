<?php namespace App\Repositories;
/**
 * Copyright 2023 OpenStack Foundation
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

use Auth\Repositories\IUserActionRepository;
use Models\UserAction;

/**
 * Class DoctrineUserActionRepository
 * @package App\Repositories
 */
final class DoctrineUserActionRepository
    extends ModelDoctrineRepository implements IUserActionRepository
{

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [
            'realm' => 'e.realm',
            'from_ip' => 'e.from_ip',
            'user_action' => 'e.user_action',
            'created_at' => 'e.created_at',
        ];
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [
            'owner_id' => 'e.owner:json_int',
            'realm' => 'e.realm:json_string',
            'from_ip' => 'e.from_ip:json_string',
            'user_action' => 'e.user_action:json_string',
            'created_at' => 'e.created_at:json_string',
        ];
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return UserAction::class;
    }
}