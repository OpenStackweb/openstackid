<?php namespace Database\Migrations;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\libs\OAuth2\IUserScopes;
use Database\Seeders\SeedUtils;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
/**
 * Class Version20250805084926
 * @package Database\Migrations
 */
class Version20250805084926 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::UserGroupWrite,
                'short_description'  => 'Allows associate Users to Groups.',
                'description'        => 'Allows associate Users to Groups.',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ]
        ], 'users');

        SeedUtils::seedApiEndpoints('users', [
            [
                'name' => 'add-user-to-groups',
                'active' => true,
                'route' => '/api/v1/users/{id}/groups',
                'http_method' => 'PUT',
                'scopes' => [
                    IUserScopes::UserGroupWrite
                ],
            ],
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
