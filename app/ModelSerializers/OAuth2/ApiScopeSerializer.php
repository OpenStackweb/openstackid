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
use Models\OAuth2\ApiScope;
/**
 * Class ApiScopeSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ApiScopeSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'Name'             => 'name:json_string',
        'Description'      => 'description:json_string',
        'ShortDescription' => 'short_description:json_string',
        'Active'           => 'active:json_boolean',
        'Default'          => 'default:json_boolean',
        'System'           => 'system:json_boolean',
        'AssignedByGroups' => 'assigned_by_groups:json_boolean',
        'ApiId'            => 'api_id:json_int',
    ];

    protected static $allowed_relations = [
        'scope_groups',
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
        $scope = $this->object;
        if(!$scope instanceof ApiScope) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('scope_groups', $relations)){
            $res = [];
            foreach ($scope->getScopeGroups() as $scope_group){
                $res[]= $scope_group->getId();
            }
            $values['scope_groups'] = $res;
        }
        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'scope_groups': {
                        $res = [];
                        unset($values['scope_groups']);
                        foreach ($scope->getScopeGroups() as $scope_group){
                            $res[]= SerializerRegistry::getInstance()->getSerializer($scope_group)->serialize();
                        }
                        $values['scope_groups'] = $res;
                    }
                    break;
                    case 'api': {
                        unset($values['api_id']);
                        $values['api'] = SerializerRegistry::getInstance()->getSerializer($scope->getApi())->serialize();
                    }
                    break;
                }
            }
        }
        return $values;
    }
}