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
use Auth\Group;
use Auth\User;
use Illuminate\Support\Facades\Auth;

/**
 * Class BaseUserSerializer
 * @package App\ModelSerializers\Auth
 */
class BaseUserSerializer extends BaseSerializer
{
    protected static $array_mappings = [
        'FirstName' => 'first_name:json_string',
        'LastName' => 'last_name:json_string',
        'Pic' => 'pic:json_url',
    ];
}

final class PublicUserSerializer extends BaseUserSerializer
{

}

final class PrivateUserSerializer extends BaseUserSerializer
{

    protected static $array_mappings = [
        'Email' => 'email:json_string',
        'Bio' => 'bio:json_string',
        'Company' => 'company:json_string',
        'Gender' => 'gender:json_string',
        'SpamType' => 'spam_type:json_string',
        'Identifier' => 'identifier:json_string',
        'LastLoginDate' => 'last_login_date:datetime_epoch',
        'Active' => 'active:json_boolean',
        'EmailVerified' => 'email_verified:json_boolean'
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
        $user = $this->object;
        if (!$user instanceof User) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        $groups = [];
        foreach ($user->getGroups() as $group) {
            if (!$group instanceof Group) continue;
            $groups[] = $group->getSlug();
        }

        $values['groups'] = $groups;
        return $values;
    }
}