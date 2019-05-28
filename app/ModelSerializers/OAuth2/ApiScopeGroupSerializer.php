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
use Models\OAuth2\ApiScopeGroup;
/**
 * Class ApiScopeGroupSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ApiScopeGroupSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Name'             => 'name:json_string',
        'Description'      => 'description:json_string',
        'Active'           => 'active:json_boolean',
    ];

    protected static $allowed_relations = [
        'scopes',
        'users',
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
        $group = $this->object;
        if(!$group instanceof ApiScopeGroup) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('scopes', $relations)){
            $res = [];
            foreach ($group->getScopes() as $scope){
                $res[]= $scope->getId();
            }
            $values['scopes'] = $res;
        }

        if(in_array('users', $relations)){
            $res = [];
            foreach ($group->getUsers() as $user){
                $res[]= $user->getId();
            }
            $values['users'] = $res;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'scopes': {
                        $res = [];
                        unset($values['scopes']);
                        foreach ($group->getScopes() as $scope){
                            $res[]= SerializerRegistry::getInstance()->getSerializer($scope)->serialize();
                        }
                        $values['scopes'] = $res;
                    }
                        break;
                    case 'users': {
                        $res = [];
                        unset($values['users']);
                        foreach ($group->getUsers() as $user){
                            $res[]= SerializerRegistry::getInstance()->getSerializer($user)->serialize();
                        }
                        $values['users'] = $res;
                    }
                        break;
                }
            }
        }
        return $values;
    }
}