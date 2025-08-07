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

use App\libs\Auth\Models\IGroupSlugs;
use App\libs\OAuth2\IGroupScopes;
use Auth\Group;
use Database\Seeders\SeedUtils;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class Version20250807173401
 * @package Database\Migrations
 */
class Version20250807173401 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        SeedUtils::seedApiEndpoints('users', [
            [
                'name' => 'get-user-by-id-v2',
                'active' => true,
                'route' => '/api/v2/users/{id}',
                'http_method' => 'GET',
                'scopes' => [
                    \App\libs\OAuth2\IUserScopes::ReadAll
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
