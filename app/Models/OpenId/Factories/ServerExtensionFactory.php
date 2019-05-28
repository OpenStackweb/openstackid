<?php namespace App\Models\OpenId\Factories;
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
use Models\OpenId\ServerExtension;
/**
 * Class ServerExtensionFactory
 * @package App\Models\OpenId\Factories
 */
final class ServerExtensionFactory
{
    /**
     * @param array $payload
     * @return ServerExtension
     */
    public static function build(array $payload):ServerExtension {
        return self::populate(new ServerExtension, $payload);
    }

    /**
     * @param ServerExtension $ext
     * @param array $payload
     * @return ServerExtension
     */
    public static function populate(ServerExtension $ext, array $payload): ServerExtension {
        if(isset($payload['name']))
            $ext->setName(trim($payload['name']));
        if(isset($payload['namespace']))
            $ext->setNamespace(trim($payload['namespace']));
        if(isset($payload['extension_class']))
            $ext->setExtensionClass(trim($payload['extension_class']));
        if(isset($payload['description']))
            $ext->setDescription(trim($payload['description']));
        if(isset($payload['view_name']))
            $ext->setViewName(trim($payload['view_name']));
        if(isset($payload['active']))
            $ext->setActive(boolval($payload['active']));
        return $ext;
    }
}