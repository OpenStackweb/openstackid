<?php namespace Database\Seeders;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
/**
 * Class DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $this->call(OpenIdExtensionsSeeder::class);
        $this->call(ServerConfigurationSeeder::class);

        DB::table('oauth2_api_endpoint_api_scope')->delete();
        DB::table('oauth2_client_api_scope')->delete();
        DB::table('oauth2_api_scope')->delete();
        DB::table('oauth2_api_endpoint')->delete();
        DB::table('oauth2_api')->delete();
        DB::table('oauth2_resource_server')->delete();

        $this->call(ResourceServerSeeder::class);
        $this->call(ApiSeeder::class);
        $this->call(ApiScopeSeeder::class);
        $this->call(ApiEndpointSeeder::class);
    }
}
