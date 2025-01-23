<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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
use Auth\User;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class OAuth2UserUpdateApiTest
 * @package Tests
 */

final class OAuth2UserUpdateApiTest extends OAuth2ProtectedApiTest
{
    public function testUserCreate()
    {
        $first_name = 'test_'. str_random(16);

        $data = [
            'first_name'    => $first_name,
            'last_name'     => 'test_'. str_random(16),
            'email'         => 'test_'. str_random(16) . '@test.com',
            'company'       => 'test_'. str_random(16)
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action
        (
            "POST",
            "Api\\OAuth2\\OAuth2UserApiController@create",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue($response->first_name == $first_name);
    }

    public function testUserUpdate()
    {
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $this->assertTrue(!is_null($user));

        $new_first_name = 'test_'. str_random(16);

        $data = [
            'first_name'    => $new_first_name,
            'last_name'     => 'test_'. str_random(16),
            'company'       => 'test_'. str_random(16),
        ];

        $params = [
            'id'   => $user->id,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action
        (
            "PUT",
            "Api\\OAuth2\\OAuth2UserApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue($response->first_name == $new_first_name);
    }

    protected function getScopes()
    {
        return [
            "openid",
            IUserScopes::Write,
        ];
    }
}
