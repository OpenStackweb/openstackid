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

use Models\OAuth2\Api;
use Models\OAuth2\ApiScope;
/**
 * Class ApiScopeFactory
 * @package App\Models\OAuth2\Factories
 */
final class ApiScopeFactory
{
    /**
     * @param array $payload
     * @return ApiScope
     */
    public static function build(array $payload): ApiScope {
        return self::populate(new ApiScope, $payload);
    }

    /**
     * @param ApiScope $scope
     * @param array $payload
     * @return ApiScope
     */
    public static function populate(ApiScope $scope, array $payload):ApiScope
    {
        if(isset($payload['name']))
            $scope->setName(trim($payload['name']));

        if(isset($payload['description']))
            $scope->setDescription(trim($payload['description']));

        if(isset($payload['short_description']))
            $scope->setShortDescription(trim($payload['short_description']));

        if(isset($payload['active']))
            $scope->setActive(boolval($payload['active']));

        if(isset($payload['default']))
            $scope->setDefault(boolval($payload['default']));

        if(isset($payload['default']))
            $scope->setDefault(boolval($payload['default']));

        if(isset($payload['system']))
            $scope->setSystem(boolval($payload['system']));

        if(isset($payload['assigned_by_groups']))
            $scope->setAssignedByGroups(boolval($payload['assigned_by_groups']));

        if(isset($payload['api']) && $payload['api'] instanceof Api) {
            $api = $payload['api'];
            $api->addScope($scope);
        }

        return $scope;
    }
}