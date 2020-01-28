<?php namespace Database\Migrations;
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
use App\libs\OAuth2\IUserScopes;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
/**
 * Class Version20190829142736
 * @package Database\Migrations
 */
class Version20190829142736 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        \SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::ReadAll,
                'short_description'  => 'Allows access to users info',
                'description'        => 'This scope value requests access to users info',
                'system'             => false,
                'default'            => false,
                'groups'             => true,
            ],
        ], 'users');

        \SeedUtils::seedApiEndpoints('users', [
                // get users
                [
                    'name' => 'get-users',
                    'active' => true,
                    'route' => '/api/v1/users',
                    'http_method' => 'GET',
                    'scopes' => [
                         IUserScopes::ReadAll
                    ],
                ]

            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
