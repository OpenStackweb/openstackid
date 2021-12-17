<?php namespace Database\Migrations;
/**
 * Copyright 2017 OpenStack Foundation
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
use Database\Seeders\SeedUtils;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
/**
 * Class Version20211217173208
 * @package Database\Migrations
 */
class Version20211217173208 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        SeedUtils::seedApiEndpoints('user-registration', [
            [
                'name' => 'user-registration-request-get-all',
                'active' => true,
                'route' => '/api/v1/user-registration-requests',
                'http_method' => 'GET',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::Registration
                ],
            ],
            [
                'name' => 'user-registration-request-update',
                'active' => true,
                'route' => '/api/v1/user-registration-requests/{id}',
                'http_method' => 'PUT',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::Registration
                ],
            ],
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
