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
use Models\OAuth2\ResourceServer;
/**
 * Class ApiFactory
 * @package App\Models\OAuth2\Factories
 */
final class ApiFactory
{
    /**
     * @param array $payload
     * @return Api
     */
    public static function build(array $payload):Api
    {
        return self::populate(new Api, $payload);
    }

    /**
     * @param Api $api
     * @param array $payload
     * @return Api
     */
    public static function populate(Api $api, array $payload): Api
    {
        if(isset($payload['name']))
            $api->setName(trim($payload['name']));

        if(isset($payload['description']))
            $api->setDescription(trim($payload['description']));

        if(isset($payload['active']))
            $api->setActive(boolval($payload['active']));

        if(isset($payload['resource_server']) && $payload['resource_server'] instanceof ResourceServer){
            $resource_server = $payload['resource_server'];
            $resource_server->addApi($api);
        }

        return $api;
    }
}