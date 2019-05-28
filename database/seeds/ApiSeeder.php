<?php
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
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\ResourceServer;
use Models\OAuth2\Api;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
/**
 * Class ApiSeeder
 */
class ApiSeeder extends Seeder {

    public function run()
    {
        DB::table('oauth2_api_endpoint_api_scope')->delete();
        DB::table('oauth2_client_api_scope')->delete();
        DB::table('oauth2_api_scope')->delete();
        DB::table('oauth2_api')->delete();

        $resource_server_repository = EntityManager::getRepository(ResourceServer::class);
        $rs = $resource_server_repository->findOneBy([
            'friendly_name' => 'OpenStackId server'
        ]);

        $api = new Api();
        $api->setName('users');
        $api->setActive(true);
        $api->setDescription('User Info API');
        $api->setResourceServer($rs);

        EntityManager::persist($api);

        EntityManager::flush();

        $api = new Api();
        $api->setName('user-registration');
        $api->setActive(true);
        $api->setDescription('User Registration API');
        $api->setResourceServer($rs);

        EntityManager::persist($api);

        EntityManager::flush();
    }
}