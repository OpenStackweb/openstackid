<?php namespace Tests;
/**
 * Copyright 2016 OpenStack Foundation
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
use OAuth2\ResourceServer\IUserService;
/**
 * Class OAuth2UserServiceApiTest
 */
final class OAuth2UserServiceApiTest extends OAuth2ProtectedApiTest {

    /**
     * @covers OAuth2UserApiController::get()
     */
    public function testUpdateMe(){

        $first_name_val = 'test_'. str_random(16);
        $data = [
            'first_name' => $first_name_val,
        ];

        $params = [
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action
        (
            "PUT",
            "Api\\OAuth2\\OAuth2UserApiController@UpdateMe",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $user = json_decode($content);

        $this->assertTrue($user->first_name == $first_name_val);

    }

    /**
     * @covers OAuth2UserApiController::get()
     */
    public function testGetInfo(){

        $response = $this->action("GET", "Api\OAuth2\OAuth2UserApiController@me",
            [],
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $this->assertResponseStatus(200);
        $content   = $response->getContent();
        $user_info = json_decode($content);
    }

    public function testGetInfoCORS(){
        $response = $this->action("OPTIONS", "Api\OAuth2\OAuth2UserApiController@me",
            [],
            [],
            [],
            [],
            array(
                "HTTP_Authorization"                  => " Bearer " .$this->access_token,
                'HTTP_Origin'                         => array('www.test.com'),
                'HTTP_Host'                           => 'local.openstackid.openstack.org',
                'HTTP_Access-Control-Request-Method'  => 'GET',
            ));

        // check PreflightRequest
        $this->assertResponseStatus(204);
        $headers = $response->headers;

        $this->assertTrue($headers->has("Access-Control-Allow-Methods"));
        $this->assertTrue($headers->has("Access-Control-Allow-Headers"));
    }

    protected function getScopes()
    {
        $scope = array(
            IUserService::UserProfileScope_Address,
            IUserService::UserProfileScope_Email,
            IUserService::UserProfileScope_Profile,
            IUserScopes::MeWrite,
        );

        return $scope;
    }
}