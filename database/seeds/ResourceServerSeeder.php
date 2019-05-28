<?php
/**
 * Copyright 2015 OpenStack Foundation
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
use Models\OAuth2\ResourceServer;
use Illuminate\Database\Seeder;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
/**
 * Class ResourceServerSeeder
 */
class ResourceServerSeeder extends Seeder {

    public function run()
    {
        DB::table('oauth2_resource_server')->delete();

        $current_realm = Config::get('app.url');

        $res = @parse_url($current_realm);

        $rs = new ResourceServer();
        $rs->setFriendlyName('OpenStackId server');
        $rs->setHost($res['host']);
        $rs->setIps('127.0.0.1');
        $rs->setActive(true);
        EntityManager::persist($rs);

        EntityManager::flush();
    }
}