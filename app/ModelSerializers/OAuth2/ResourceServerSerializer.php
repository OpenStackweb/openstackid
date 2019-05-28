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
use Models\OAuth2\ResourceServer;
/**
 * Class ResourceServerSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ResourceServerSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'FriendlyName' => 'friendly_name:json_string',
        'Host' => 'host:json_string',
        'Ips' => 'ips:json_string',
        'Active' => 'active:json_boolean',
        'ClientId' => 'client_id:json_int',
    ];

    protected static $allowed_relations = [
        'apis',
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
        $resource_server = $this->object;
        if (!$resource_server instanceof ResourceServer) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('apis', $relations)) {
            $res = [];
            foreach ($resource_server->getApis() as $api) {
                $res[] = $api->getId();
            }
            $values['apis'] = $res;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                    case 'apis':
                        {
                            unset($values['apis']);
                            $res = [];
                            foreach ($resource_server->getApis() as $api) {
                                $res[] = SerializerRegistry::getInstance()->getSerializer($api)->serialize();
                            }
                            $values['apis'] = $res;
                        }
                        break;
                    case 'client':
                        {

                            if ($resource_server->hasClient()) {
                                unset($values['client_id']);
                                $values['client'] = SerializerRegistry::getInstance()->getSerializer($resource_server->getClient())->serialize();
                            }
                        }
                        break;

                }
            }
        }
        return $values;
    }
}