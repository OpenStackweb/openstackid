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

/**
 * Class OAuth2UserRegistrationServiceApiTest
 * @package Tests
 */
final class OAuth2UserRegistrationServiceApiTest extends \OAuth2ProtectedApiTest
{


    public function testRegisterUserRequestCreation()
    {

        $data = [
            'email'      => 'test_'. str_random(16).'@test.com',
            'first_name' => 'test_'. str_random(16),
            'last_name'  => 'test_'. str_random(16),
        ];

        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action
        (
            "POST",
            "Api\OAuth2\OAuth2UserRegistrationRequestApiController@register",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $user_registration_request = json_decode($content);

        $this->assertTrue(!empty($user_registration_request->hash));
        $params = [
            'token' => $user_registration_request->hash,
            'redirect_uri' => 'https://www.test.com/oauth2'
            ];

        $response = $this->action
        (
            "GET",
            "Auth\PasswordSetController@showPasswordSetForm",
            $params,
            [],
            [],
            [],
            [],
            null
        );

        $this->assertResponseStatus(200);

    }

    protected function getScopes()
    {
        $scope = [
            'request-user-registration'
        ];

        return $scope;
    }
}