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
use Models\OAuth2\Api;
/**
 * Class ApiSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ApiSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Description' => 'description:json_string',
        'Logo' => 'logo:json_string',
        'Active' => 'active:json_boolean',
        'ResourceServerId' => 'resource_server_id:json_int',
    ];

    protected static $allowed_relations = [
        'scopes',
        'endpoints',
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
        $api = $this->object;
        if(!$api instanceof Api) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('scopes', $relations)){
            $res = [];
            foreach ($api->getScopes() as $scope){
                $res[]= $scope->getId();
            }
            $values['scopes'] = $res;
        }

        if(in_array('endpoints', $relations)){
            $res = [];
            foreach ($api->getEndpoints() as $endpoint){
                $res[]= $endpoint->getId();
            }
            $values['endpoints'] = $res;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'scopes': {
                        unset($values['scopes']);
                        $res = [];
                        foreach ($api->getScopes() as $scope){
                            $res[]= SerializerRegistry::getInstance()->getSerializer($scope)->serialize();
                        }
                        $values['scopes'] = $res;
                    }
                    break;
                    case 'endpoints': {
                        unset($values['endpoints']);
                        $res = [];
                        foreach ($api->getEndpoints() as $endpoint){
                            $res[]= SerializerRegistry::getInstance()->getSerializer($endpoint)->serialize();
                        }
                        $values['endpoints'] = $res;
                    }
                        break;
                    case 'resource_server': {
                        if($api->haResourceServer()) {
                            unset($values['resource_server_id']);
                            $values['resource_server'] = SerializerRegistry::getInstance()->getSerializer($api->getResourceServer())->serialize();
                        }
                    }
                    break;

                }
            }
        }
        return $values;
    }
}