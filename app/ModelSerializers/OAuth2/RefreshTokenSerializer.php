<?php namespace App\ModelSerializers\OAuth2;
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
use Models\OAuth2\RefreshToken;
/**
 * Class RefreshTokenSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class RefreshTokenSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Value'             => 'value:json_string',
        'FromIp'            => 'from_ip:json_string',
        'RemainingLifetime' => 'remaining_lifetime:json_int',
        'Lifetime'          => 'lifetime:json_int',
        'Scope'             => 'scope:json_string',
        'Audience'          => 'audience:json_string',
        'ClientId'          => 'client_id:json_int',
        'OwnerId'           => 'user_id:json_int',
        'Void'              => 'is_void:json_boolean',
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
        $token = $this->object;
        if(!$token instanceof RefreshToken) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['client_type'] = $token->getClient()->getApplicationType();
        $values['client_name'] = $token->getClient()->getApplicationName();
        return $values;
    }

}