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

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\ResourceServer;
use Models\OAuth2\Api;
use Database\Seeders\SeedUtils;
use App\libs\OAuth2\IUserScopes;
/**
 * Class Version20250127144553
 * @package Database\Migrations
 */
final class Version20250127144553 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        SeedUtils::seedApiEndpoints('users', [
                [
                    'name' => 'create-user',
                    'active' => true,
                    'route' => '/api/v1/users',
                    'http_method' => 'POST',
                    'scopes'      => [
                         IUserScopes::Write
                    ],
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
