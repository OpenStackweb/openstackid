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
use Auth\Group;
use Auth\User;
use LaravelDoctrine\ORM\Facades\EntityManager;
use OAuth2\ResourceServer\IUserService;
use OAuth2ProtectedServiceAppApiTestCase;

/**
 * Class OAuth2UserServiceApiTest
 */
final class OAuth2UserApiTest extends OAuth2ProtectedServiceAppApiTestCase {

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
            "Api\\OAuth2\\OAuth2UserApiController@updateMe",
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

    public function testGetUserByIdV1(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'  => $user->getId()
        ];

        $response = $this->action(
            "GET",
            "Api\OAuth2\OAuth2UserApiController@get",
            $params,
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $content   = $response->getContent();
        $this->assertResponseStatus(200);
        $user = json_decode($content);
        $this->assertNotNull($user);
    }

     public function testGetUserByIdV2(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'  => $user->getId(),
            'expand' => 'groups'
        ];

        $response = $this->action(
            "GET",
            "Api\OAuth2\OAuth2UserApiController@getV2",
            $params,
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token_service_app_type));

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $user = json_decode($content);
        $this->assertNotNull($user);
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

    public function testGetAllWithoutFilter(){
        $response = $this->action("GET", "Api\OAuth2\OAuth2UserApiController@getAll",
            [],
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $this->assertResponseStatus(200);
        $content   = $response->getContent();
        $page = json_decode($content);
        $this->assertTrue($page->total > 0);
    }

    public function testUpdateUserGroups(){
        $repo = EntityManager::getRepository(Group::class);
        $group = $repo->getOneBySlug('raw-users');

        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id' => $user->getId()
        ];

        $data = [
            'groups' => [$group->getId()],
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token_service_app_type,
            "CONTENT_TYPE"        => "application/json"
        ];

        $this->action(
            "PUT",
            "Api\OAuth2\OAuth2UserApiController@updateUserGroups",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $user = $repo->getById($user->getId());
        $this->assertNotNull($user);
        $this->assertCount(1, $user->getGroups());
    }

    protected function getScopes()
    {
        $scope = array(
            IUserService::UserProfileScope_Address,
            IUserService::UserProfileScope_Email,
            IUserService::UserProfileScope_Profile,
            IUserScopes::MeWrite,
            IUserScopes::ReadAll,
            IUserScopes::UserGroupWrite
        );

        return $scope;
    }
}