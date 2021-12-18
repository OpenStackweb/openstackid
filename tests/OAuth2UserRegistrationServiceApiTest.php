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
/**
 * Class OAuth2UserRegistrationServiceApiTest
 * @package Tests
 */
final class OAuth2UserRegistrationServiceApiTest extends OAuth2ProtectedApiTest
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
            "Api\\OAuth2\\OAuth2UserRegistrationRequestApiController@register",
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
    }

    public function testRegistrationRequestUpdate()
    {
        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $data = [
            'email'      => 'test_'. str_random(16).'@test.com',
            'first_name' => 'test_'. str_random(16),
            'last_name'  => 'test_'. str_random(16),
        ];

        //Create new registration request
        $response = $this->action
        (
            "POST",
            "Api\\OAuth2\\OAuth2UserRegistrationRequestApiController@register",
            [],
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        //Fetch created registration request
        $params = [
            'filter'   => 'email==' . $data['email'],
        ];

        $response = $this->action
        (
            "GET",
            "Api\\OAuth2\\OAuth2UserRegistrationRequestApiController@getAll",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $response = json_decode($content);

        $this->assertTrue($response->total == 1);

        //Update fetched registration request
        $params = [
            'id'  => $response->data[0]->id,
        ];

        $new_first_name = 'test_updated_'. str_random(16);

        $update_data = [
            'first_name' => $new_first_name,
            'last_name'  => 'test_updated_'. str_random(16),
            'company'    => 'test_updated_'. str_random(16),
            'country'    => 'US',
        ];

        $response = $this->action
        (
            "PUT",
            "Api\\OAuth2\\OAuth2UserRegistrationRequestApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($update_data)
        );

        $this->assertResponseStatus(201);

        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue($response->first_name == $new_first_name);
    }

    protected function getScopes()
    {
        $scope = [
            "openid",
            IUserScopes::Registration,
        ];

        return $scope;
    }
}