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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\ResourceServer;
use Models\OAuth2\Api;
use ApiScopeSeeder;
use ApiEndpointSeeder;
use App\libs\OAuth2\IUserScopes;
/**
 * Class Version20190729150610
 * @package Database\Migrations
 */
final class Version20190729150610 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $resource_server_repository = EntityManager::getRepository(ResourceServer::class);
        $rs = $resource_server_repository->findOneBy([
            'friendly_name' => 'openstack id server'
        ]);

        if(is_null($rs)) return;

        $api = new Api();
        $api->setName('user-registration');
        $api->setActive(true);
        $api->setDescription('User Registration API');
        $api->setResourceServer($rs);

        EntityManager::persist($api);

        EntityManager::flush();

        ApiScopeSeeder::seedScopes([
            [
                'name'               => IUserScopes::Registration,
                'short_description'  => 'Allows to request user registrations.',
                'description'        => 'Allows to request user registrations.',
                'system'             => false,
                'default'            => false,
                'groups'             => true,
            ],

        ], 'user-registration');

        ApiEndpointSeeder::seedApiEndpoints('user-registration', [
                [
                    'name' => 'request-user-registration',
                    'active' => true,
                    'route' => '/api/v1/user-registration-requests',
                    'http_method' => 'POST',
                    'scopes'      => [
                        IUserScopes::Registration
                    ],
                ],

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
