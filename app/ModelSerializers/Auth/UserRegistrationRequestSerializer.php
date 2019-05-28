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
use App\libs\Auth\Models\UserRegistrationRequest;
use App\ModelSerializers\BaseSerializer;
use Illuminate\Support\Facades\URL;
/**
 * Class UserRegistrationRequestSerializer
 * @package App\ModelSerializers\Auth
 */
final class UserRegistrationRequestSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Email'      => 'email:json_string',
        'FirstName'  => 'first_name:json_string',
        'LastName'   => 'last_name:json_string',
        'Country'    => 'country:json_string',
        'Hash'       => 'hash:json_string',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $request = $this->object;
        if(!$request instanceof UserRegistrationRequest) return [];
        if(!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['set_password_link'] = URL::route("password.set", ["token" => $request->getHash()]);
        return $values;
    }
}