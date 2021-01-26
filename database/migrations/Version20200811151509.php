<?php namespace Database\Migrations;
/**
 * Copyright 2020 OpenStack Foundation
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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Database\Seeders\SeedUtils;
/**
 * Class Version20200811151509
 * @package Database\Migrations
 */
class Version20200811151509 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {

        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::MeRead,
                'short_description'  => 'Allows access to read your Profile',
                'description'        => 'Allows access to read your Profile',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ],

        ], 'users');

        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::MeWrite,
                'short_description'  => 'Allows access to write your Profile',
                'description'        => 'Allows access to write your Profile',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ],

        ], 'users');

        SeedUtils::seedApiEndpoints('users', [
            [
                'name' => 'update-my-user',
                'active' => true,
                'route' => '/api/v1/users/me',
                'http_method' => 'PUT',
                'scopes' => [
                    \App\libs\OAuth2\IUserScopes::MeWrite
                ],
            ],
            [
                'name' => 'update-my-user-pic',
                'active' => true,
                'route' => '/api/v1/users/me/pic',
                'http_method' => 'PUT',
                'scopes' => [
                    \App\libs\OAuth2\IUserScopes::MeWrite
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
