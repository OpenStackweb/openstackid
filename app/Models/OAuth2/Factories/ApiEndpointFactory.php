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
use Models\OAuth2\ApiEndpoint;
/**
 * Class ApiEndpointFactory
 * @package App\Models\OAuth2\Factories
 */
final class ApiEndpointFactory
{
    public static function build(array $payload):ApiEndpoint{
        return self::populate(new ApiEndpoint, $payload);
    }

    public static function populate(ApiEndpoint $endpoint, $payload):ApiEndpoint{

        if(isset($payload['name']))
            $endpoint->setName(trim($payload['name']));

        if(isset($payload['description']))
            $endpoint->setDescription(trim($payload['description']));

        if(isset($payload['route']))
            $endpoint->setRoute(trim($payload['route']));

        if(isset($payload['http_method']))
            $endpoint->setHttpMethod(trim($payload['http_method']));

        if(isset($payload['active']))
            $endpoint->setStatus(boolval($payload['active']));

        if(isset($payload['allow_cors']))
            $endpoint->setAllowCors(boolval($payload['allow_cors']));

        if(isset($payload['rate_limit']))
            $endpoint->setRateLimit(intval($payload['rate_limit']));

        if(isset($payload['api']) && $payload['api'] instanceof Api) {
            $api = $payload['api'];
            $api->addEndpoint($endpoint);
        }

        return $endpoint;
    }
}