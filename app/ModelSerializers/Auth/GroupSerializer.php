<?php namespace App\ModelSerializers\Auth;
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
use App\ModelSerializers\BaseSerializer;
/**
 * Class GroupSerializer
 * @package App\ModelSerializers\Auth
 */
class PublicGroupSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Name'    => 'name:json_string',
        'Slug'    => 'slug:json_string',
        'Active'  => 'active:json_boolean',
        'Default' => 'default:json_boolean',
    ];
}