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
use Illuminate\Database\Seeder;
use OpenId\Extensions\Implementations\OpenIdAXExtension;
use OpenId\Extensions\Implementations\OpenIdSREGExtension;
use OpenId\Extensions\Implementations\OpenIdOAuth2Extension;
use OpenId\Extensions\Implementations\OpenIdSREGExtension_1_0;
use Illuminate\Support\Facades\DB;
/**
 * Class OpenIdExtensionsSeeder
 */
class OpenIdExtensionsSeeder extends Seeder {

    public function run()
    {
        DB::table('server_extensions')->delete();

        $extensions = [
            array(
                'name'            => 'OAUTH2',
                'namespace'       => 'http://specs.openid.net/extensions/oauth/2.0',
                'active'          => true,
                'extension_class' => OpenIdOAuth2Extension::class,
                'description'     => 'The OpenID OAuth2 Extension describes how to make the OpenID Authentication and OAuth2 Core specifications work well together.',
                'view_name'       => 'extensions.oauth2',
            ),
            array(
                'name'            => 'SREG',
                'namespace'       => 'http://openid.net/extensions/sreg/1.1',
                'active'          => true,
                'extension_class' => OpenIdSREGExtension::class,
                'description'     => 'OpenID Simple Registration 1.1 is an extension to the OpenID Authentication protocol that allows for very light-weight profile exchange.',
                'view_name'       => 'extensions.sreg',
            ),
            array(
                'name'            => 'SREG_1_0',
                'namespace'       => 'http://openid.net/sreg/1.0',
                'active'          => true,
                'extension_class' => OpenIdSREGExtension_1_0::class,
                'description'     => 'OpenID Simple Registration 1.0 is an extension to the OpenID Authentication protocol that allows for very light-weight profile exchange.',
                'view_name'       => 'extensions.sreg',
            ),
            array(
                'name'            => 'AX',
                'namespace'       => 'http://openid.net/srv/ax/1.0',
                'active'          => true,
                'extension_class' => OpenIdAXExtension::class,
                'description'     => 'OpenID service extension for exchanging identity information between endpoints',
                'view_name'       =>'extensions.ax',
            ),
        ];

        foreach ($extensions as $extension){
            SeedUtils::createServerExtension($extension);
        }
    }



}
