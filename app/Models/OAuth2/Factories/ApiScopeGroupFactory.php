<?php namespace App\Models\OAuth2\Factories;
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
use Models\OAuth2\ApiScopeGroup;
/**
 * Class ApiScopeGroupFactory
 * @package App\Models\OAuth2\Factories
 */
final class ApiScopeGroupFactory
{
    /**
     * @param array $payload
     * @return ApiScopeGroup
     */
    public static function build(array $payload): ApiScopeGroup {
        return self::populate(new ApiScopeGroup, $payload);
    }

    /**
     * @param ApiScopeGroup $group
     * @param array $payload
     * @return ApiScopeGroup
     */
    public static function populate(ApiScopeGroup $group, array $payload):ApiScopeGroup{
        if(isset($payload['name']))
            $group->setName(trim($payload['name']));
        if(isset($payload['active']))
            $group->setActive(boolval($payload['active']));
        if(isset($payload['description']))
            $group->setDescription(trim($payload['description']));
        return $group;
    }
}