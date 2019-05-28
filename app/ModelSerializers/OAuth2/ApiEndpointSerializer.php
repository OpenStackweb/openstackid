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
use App\ModelSerializers\SerializerRegistry;
use Models\OAuth2\ApiEndpoint;
/**
 * Class ApiEndpointSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ApiEndpointSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Name'             => 'name:json_string',
        'Description'      => 'description:json_string',
        'Active'           => 'active:json_boolean',
        'AllowCors'        => 'allow_cors:json_boolean',
        'AllowCredentials' => 'allow_credentials:json_boolean',
        'RateLimit'        => 'rate_limit:json_int',
        'RateLimitDeca'    => 'rate_limit_decay:json_int',
        'ApiId'            => 'api_id:json_int',
    ];

    protected static $allowed_relations = [
        'scopes',
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
        $endpoint = $this->object;
        if(!$endpoint instanceof ApiEndpoint) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('scopes', $relations)){
            $res = [];
            foreach ($endpoint->getScopes() as $scope){
                $res[]= $scope->getId();
            }
            $values['scopes'] = $res;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'scopes': {
                        $res = [];
                        unset($values['scopes']);
                        foreach ($endpoint->getScopes() as $scope){
                            $res[]= SerializerRegistry::getInstance()->getSerializer($scope)->serialize();
                        }
                        $values['scopes'] = $res;
                    }
                        break;
                    case 'api': {
                        unset($values['api_id']);
                        $values['api'] = SerializerRegistry::getInstance()->getSerializer($endpoint->getApi())->serialize();
                    }
                        break;
                }
            }
        }
        return $values;
    }
}