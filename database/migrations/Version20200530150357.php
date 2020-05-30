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
use SeedUtils;
/**
 * Class Version20200530150357
 * @package Database\Migrations
 */
class Version20200530150357 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if(!SeedUtils::seedApi("sso", "SSO Integration API")) return;

        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::SSO,
                'short_description'  => 'Allows SSO integration',
                'description'        => 'Allows SSO integration',
                'system'             => false,
                'default'            => false,
                'groups'             => true,
            ],

        ], 'sso');

        SeedUtils::seedApiEndpoints('sso', [
            [
                'name' => 'sso-disqus',
                'active' => true,
                'route' => '/api/v1/sso/disqus/{forum_slug}/profile',
                'http_method' => 'GET',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::SSO
                ],
            ],
            [
                'name' => 'sso-rocket-chat',
                'active' => true,
                'route' => '/api/v1/sso/rocket-chat/{forum_slug}/profile',
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
