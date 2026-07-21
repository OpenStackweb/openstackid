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

    public function testGetUserByIdV1WithFieldsPassthrough(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'     => $user->getId(),
            'fields' => 'public_profile_allow_chat_with_me,first_name,last_name,pic'
        ];

        $response = $this->action(
            "GET",
            "Api\OAuth2\OAuth2UserApiController@get",
            $params,
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        // no `relations` override sent -> the default relation ('groups') still applies.
        $this->assertEqualsCanonicalizing(
            ['public_profile_allow_chat_with_me', 'first_name', 'last_name', 'pic', 'groups'],
            array_keys($payload)
        );
    }

    public function testGetUserByIdV1WithFieldsAndRelationsNone(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'        => $user->getId(),
            'fields'    => 'public_profile_allow_chat_with_me,first_name,last_name,pic',
            // 'none' is an arbitrary non-matching relation name used only to prove the override
            // suppresses the default -- there is no dedicated empty-relations syntax for this endpoint.
            'relations' => 'none'
        ];

        $response = $this->action(
            "GET",
            "Api\OAuth2\OAuth2UserApiController@get",
            $params,
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        $this->assertEqualsCanonicalizing(
            ['public_profile_allow_chat_with_me', 'first_name', 'last_name', 'pic'],
            array_keys($payload)
        );
    }

    public function testGetUserByIdV1NotFoundStillReturns404(){
        $params = [
            'id' => PHP_INT_MAX,
        ];

        $response = $this->action(
            "GET",
            "Api\OAuth2\OAuth2UserApiController@get",
            $params,
            [],
            [],
            [],
            array("HTTP_Authorization" => " Bearer " .$this->access_token));

        $this->assertResponseStatus(404);
        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $payload);
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

    public function testGetUserByIdV2WithNoParamsReturnsSameShapeAsBefore(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        // No 'fields', 'relations', or 'expand' at all -- proves the fields/relations
        // passthrough is purely additive and does not change the response for a caller
        // that sends none of the new params.
        $params = [
            'id' => $user->getId(),
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
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        // Full private field set, unchanged from before this plan -- id/timestamps/PII/groups all present.
        $this->assertEqualsCanonicalizing(
            [
                'active', 'address1', 'address2', 'bio', 'birthday', 'city', 'company',
                'country_iso_code', 'created_at', 'email', 'email_verified', 'first_name',
                'gender', 'gender_specify', 'github_user', 'groups', 'id', 'identifier',
                'irc', 'job_title', 'language', 'last_login_date', 'last_name',
                'linked_in_profile', 'phone_number', 'pic', 'post_code',
                'public_profile_allow_chat_with_me', 'public_profile_show_bio',
                'public_profile_show_email', 'public_profile_show_fullname',
                'public_profile_show_photo', 'public_profile_show_social_media_info',
                'public_profile_show_telephone_number', 'second_email', 'spam_type',
                'state', 'statement_of_interest', 'third_email', 'twitter_name',
                'updated_at', 'wechat_user',
            ],
            array_keys($payload)
        );
    }

    public function testGetUserByIdV2WithFieldsPassthrough(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'     => $user->getId(),
            'fields' => 'public_profile_allow_chat_with_me,first_name,last_name,pic'
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
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        // no `relations` override sent -> the default relation ('groups') still applies.
        $this->assertEqualsCanonicalizing(
            ['public_profile_allow_chat_with_me', 'first_name', 'last_name', 'pic', 'groups'],
            array_keys($payload)
        );
    }

    public function testGetUserByIdV2WithFieldsAndRelationsNone(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'        => $user->getId(),
            'fields'    => 'public_profile_allow_chat_with_me,first_name,last_name,pic',
            // 'none' is an arbitrary non-matching relation name used only to prove the override
            // suppresses the default -- there is no dedicated empty-relations syntax for this endpoint.
            'relations' => 'none'
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
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        $this->assertEqualsCanonicalizing(
            ['public_profile_allow_chat_with_me', 'first_name', 'last_name', 'pic'],
            array_keys($payload)
        );
    }

    public function testGetUserByIdV2ExpandOverridesRelationsNone(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'        => $user->getId(),
            'fields'    => 'public_profile_allow_chat_with_me,first_name,last_name,pic',
            // same non-matching sentinel as testGetUserByIdV2WithFieldsAndRelationsNone -- proves
            // `expand` still forces `groups` back in even though `relations` omitted it.
            'relations' => 'none',
            'expand'    => 'groups'
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
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        $this->assertArrayHasKey('groups', $payload);
        $this->assertIsArray($payload['groups']);
    }

    public function testGetUserByIdV2RelationsGroupsWithExpand(){
        $repo = EntityManager::getRepository(User::class);
        $user = $repo->getAll()[0];

        $params = [
            'id'        => $user->getId(),
            'relations' => 'groups',
            'expand'    => 'groups'
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
        $payload = json_decode($content, true);
        $this->assertNotNull($payload);
        $this->assertArrayHasKey('groups', $payload);
        $this->assertIsArray($payload['groups']);
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