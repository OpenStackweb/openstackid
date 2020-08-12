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
use OAuth2\OAuth2Protocol;
use Models\OAuth2\Api;
use Models\OAuth2\ApiScope;
use Illuminate\Database\Seeder;
use LaravelDoctrine\ORM\Facades\EntityManager;
use App\libs\OAuth2\IUserScopes;
use Illuminate\Support\Facades\DB;
/**
 * Class ApiScopeSeeder
 */
class ApiScopeSeeder extends Seeder {

    public function run()
    {
        DB::table('oauth2_api_endpoint_api_scope')->delete();
        DB::table('oauth2_client_api_scope')->delete();
        DB::table('oauth2_api_scope')->delete();
        $this->seedUsersScopes();
        $this->seedRegistrationScopes();
        $this->seedSSOScopes();
    }


    private function seedUsersScopes(){

        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::Profile,
                'short_description'  => 'Allows access to your profile info.',
                'description'        => 'This scope value requests access to the End-Users default profile Claims, which are: name, family_name, given_name, middle_name, nickname, preferred_username, profile, picture, website, gender, birthdate, zoneinfo, locale, and updated_at.',
                'system'             => false,
                'default'            => false,
            ],
            [
                'name'               => IUserScopes::Email,
                'short_description'  => 'Allows access to your email info.',
                'description'        => 'This scope value requests access to the email and email_verified Claims.',
                'system'             => false,
                'default'            => false,
            ],
            [
                'name'               => IUserScopes::Address,
                'short_description'  => 'Allows access to your Address info.',
                'description'        => 'This scope value requests access to the address Claim.',
                'system'             => false,
                'default'            => false,
            ],
            [
                'name'               => IUserScopes::ReadAll,
                'short_description'  => 'Allows access to users info',
                'description'        => 'This scope value requests access to users info',
                'system'             => false,
                'default'            => false,
                'groups'             => true,
            ],
            [
                'name'               => IUserScopes::MeRead,
                'short_description'  => 'Allows access to read your Profile',
                'description'        => 'Allows access to read your Profile',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ],
            [
                'name'               => IUserScopes::MeWrite,
                'short_description'  => 'Allows access to write your Profile',
                'description'        => 'Allows access to write your Profile',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ]
        ], 'users');

        SeedUtils::seedScopes(
            [
                [
                    'name'               => OAuth2Protocol::OpenIdConnect_Scope,
                    'short_description'  => 'OpenId Connect Protocol',
                    'description'        => 'OpenId Connect Protocol',
                    'system'             => true,
                    'default'            => true,
                ],
                [
                    'name'               => OAuth2Protocol::OfflineAccess_Scope,
                    'short_description'  => 'allow to emit refresh tokens (offline access without user presence)',
                    'description'        => 'allow to emit refresh tokens (offline access without user presence)',
                    'system'             => true,
                    'default'            => true,
                ]
            ]
        );
    }

    private function seedRegistrationScopes(){
        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::Registration,
                'short_description'  => 'Allows to request user registrations.',
                'description'        => 'Allows to request user registrations.',
                'system'             => false,
                'default'            => false,
                'groups'             => true,
            ],

        ], 'user-registration');
    }

    private function seedSSOScopes(){
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
    }
}