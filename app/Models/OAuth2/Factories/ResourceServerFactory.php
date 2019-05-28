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
use Models\OAuth2\ResourceServer;
/**
 * Class ResourceServerFactory
 * @package App\Models\OAuth2\Factories
 */
final class ResourceServerFactory
{
    /**
     * @param array $payload
     * @return ResourceServer
     */
    public static function build(array $payload):ResourceServer{
        return self::populate(new ResourceServer, $payload);
    }

    /**
     * @param ResourceServer $resource_server
     * @param array $payload
     * @return ResourceServer
     */
    public static function populate(ResourceServer $resource_server, array $payload):ResourceServer{
        if(isset($payload['host']))
            $resource_server->setHost(trim($payload['host']));
        if(isset($payload['ips']))
            $resource_server->setIps(trim($payload['ips']));
        if(isset($payload['friendly_name']))
            $resource_server->setFriendlyName(trim($payload['friendly_name']));
        if(isset($payload['active']))
            $resource_server->setActive(boolval($payload['active']));
        return $resource_server;
    }
}