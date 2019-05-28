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
use Illuminate\Support\Facades\Auth;
use Models\OAuth2\Client;
/**
 * Class ClientSerializer
 * @package App\ModelSerializers\OAuth2
 */
final class ClientSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'ApplicationName'         => 'app_name:json_string',
        'ApplicationDescription'  => 'app_description:json_string',
        'ApplicationType'         => 'application_type:json_string',
        'FriendlyApplicationType' => 'friendly_application_type:json_string',
        'Active'                  => 'active:json_boolean',
        'Locked'                  => 'locked:json_boolean',
        'ClientId'                => 'client_id:json_string',
        'ClientSecret'            => 'client_secret:json_string',
        'ClientType'              => 'client_type:json_string',
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
        $client = $this->object;
        if(!$client instanceof Client) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $current_user = Auth::user();
        if(!is_null($current_user))
            $values['is_own'] = $client->getUserId() == $current_user->getId();
        $values['modified_by'] = $client->getEditedByNice();
        return $values;
    }

}