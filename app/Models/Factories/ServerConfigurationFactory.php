<?php namespace App\Models\Factories;
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
use Models\ServerConfiguration;
/**
 * Class ServerConfigurationFactory
 * @package App\Models\Factories
 */
final class ServerConfigurationFactory
{
    /**
     * @param array $payload
     * @return ServerConfiguration
     */
    public static function build(array $payload): ServerConfiguration
    {
        return self::populate(new ServerConfiguration(), $payload);
    }

    /**
     * @param ServerConfiguration $config
     * @param array $payload
     * @return ServerConfiguration
     */
    public static function populate(ServerConfiguration $config, array $payload): ServerConfiguration
    {
        if(isset($payload['key']))
            $config->setKey($payload['key']);

        if(isset($payload['value']))
            $config->setValue($payload['value']);

        return $config;
    }
}