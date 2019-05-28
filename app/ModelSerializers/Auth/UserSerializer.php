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
 * Class BaseUserSerializer
 * @package App\ModelSerializers\Auth
 */
class BaseUserSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'FirstName'   => 'first_name:json_string',
        'LastName'    => 'last_name:json_string',
    ];
}

final class PublicUserSerializer extends BaseUserSerializer {

}

final class PrivateUserSerializer extends BaseUserSerializer {
    protected static $array_mappings = [
        'Email'          => 'email:json_string',
        'Identifier'     => 'identifier:json_string',
        'LastLoginDate'  => 'last_login_date:datetime_epoch',
        'Active'         => 'active:json_boolean',
    ];
}