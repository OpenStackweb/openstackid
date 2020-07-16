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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
/**
 * Class Version20200715195155
 * @package Database\Migrations
 */
final class Version20200715195155 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        \SeedUtils::seedApiEndpoints('sso', [
            [
                'name' => 'sso-stream-chat',
                'active' => true,
                'route' => '/api/v1/sso/stream-chat/{forum_slug}/profile',
                'http_method' => 'GET',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::SSO
                ],
            ],
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
