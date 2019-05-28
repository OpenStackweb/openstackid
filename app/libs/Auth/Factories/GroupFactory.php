<?php namespace App\libs\Auth\Factories;
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
use Auth\Group;
/**
 * Class GroupFactory
 * @package App\libs\Auth\Factories
 */
final class GroupFactory
{
    /**
     * @param array $payload
     * @return Group
     */
    public static function build(array $payload):Group{
        return self::populate(new Group, $payload);
    }

    /**
     * @param Group $group
     * @param array $payload
     * @return Group
     */
    public static function populate(Group $group, array $payload):Group{
        if(isset($payload['name']))
            $group->setName(trim($payload['name']));
        if(isset($payload['slug']))
            $group->setSlug(trim($payload['slug']));
        if(isset($payload['active']))
            $group->setActive(boolval($payload['active']));
        if(isset($payload['default']))
            $group->setDefault(boolval($payload['default']));
        return $group;
    }
}