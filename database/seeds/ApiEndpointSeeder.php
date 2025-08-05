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

use App\libs\OAuth2\IUserScopes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
/**
 * Class ApiEndpointSeeder
 */
class ApiEndpointSeeder extends Seeder
{

    public function run()
    {
        DB::table('oauth2_api_endpoint_api_scope')->delete();
        DB::table('oauth2_api_endpoint')->delete();
        $this->seedUsersEndpoints();
        $this->seedRegistrationEndpoints();
        $this->seedSSOEndpoints();
        $this->seedGroupEndpoints();
    }

    private function seedUsersEndpoints()
    {
        SeedUtils::seedApiEndpoints('users', [
                // get user info
                [
                    'name' => 'get-user-info',
                    'active' => true,
                    'route' => '/api/v1/users/me',
                    'http_method' => 'GET',
                    'scopes' => [
                       'profile', 'email', 'address'
                    ],
                ],
                // get user info 2
                [
                    'name' => 'get-user-claims-get',
                    'active' => true,
                    'route' => '/api/v1/users/info',
                    'http_method' => 'GET',
                    'scopes' => [
                        'profile', 'email', 'address'
                    ],
                ],
                // get user info 4
                [
                    'name' => 'get-user-claims-post',
                    'active' => true,
                    'route' => '/api/v1/users/info',
                    'http_method' => 'POST',
                    'scopes' => [
                        'profile', 'email', 'address'
                    ],
                ]
                ,
                // get users
                [
                    'name' => 'get-users',
                    'active' => true,
                    'route' => '/api/v1/users',
                    'http_method' => 'GET',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::ReadAll
                    ],
                ],
                // get user by id
                [
                    'name' => 'get-user-by-id',
                    'active' => true,
                    'route' => '/api/v1/users/{id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::ReadAll
                    ],
                ],
                [
                    'name' => 'update-my-user',
                    'active' => true,
                    'route' => '/api/v1/users/me',
                    'http_method' => 'PUT',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::MeWrite
                    ],
                ],
                [
                    'name' => 'create-user',
                    'active' => true,
                    'route' => '/api/v1/users',
                    'http_method' => 'POST',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::Write
                    ],
                ],
                [
                    'name' => 'update-user',
                    'active' => true,
                    'route' => '/api/v1/users/{id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::Write
                    ],
                ],
                [
                    'name' => 'update-my-user-pic',
                    'active' => true,
                    'route' => '/api/v1/users/me/pic',
                    'http_method' => 'PUT',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::MeWrite
                    ],
                ],
                [
                    'name' => 'add-user-to-groups',
                    'active' => true,
                    'route' => '/api/v1/users/{id}/groups',
                    'http_method' => 'PUT',
                    'scopes' => [
                        \App\libs\OAuth2\IUserScopes::UserGroupWrite
                    ],
                ],
            ]
        );
    }

    private function seedRegistrationEndpoints(){
        SeedUtils::seedApiEndpoints('user-registration', [
            [
                'name' => 'request-user-registration',
                'active' => true,
                'route' => '/api/v1/user-registration-requests',
                'http_method' => 'POST',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::Registration
                ],
            ],
            [
                'name' => 'user-registration-request-get-all',
                'active' => true,
                'route' => '/api/v1/user-registration-requests',
                'http_method' => 'GET',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::Registration
                ],
            ],
            [
                'name' => 'user-registration-request-update',
                'active' => true,
                'route' => '/api/v1/user-registration-requests/{id}',
                'http_method' => 'PUT',
                'scopes'      => [
                    \App\libs\OAuth2\IUserScopes::Registration
                ],
            ],
        ]);
    }

    private function seedSSOEndpoints(){
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

     private function seedGroupEndpoints(){
        SeedUtils::seedApiEndpoints('groups', [
            [
                'name' => 'get-groups',
                'active' => true,
                'route' => '/api/v1/groups',
                'http_method' => 'GET',
                'scopes' => [
                    \App\libs\OAuth2\IGroupScopes::ReadAll
                ],
            ],
        ]);
    }

}