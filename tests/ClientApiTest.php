<?php namespace Tests;
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
use OAuth2\Models\IClient;
use Auth\User;
use Models\OAuth2\Client;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class ClientApiTest
 */
class ClientApiTest extends BrowserKitTestCase {

    private $current_realm;

    private $current_host;

    protected function prepareForTests():void
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->current_realm = Config::get('app.url');
        $parts               = parse_url($this->current_realm);
        $this->current_host  = $parts['host'];

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $this->be($user);
        Session::start();
    }

    public function testGetById(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_test_app']);

        $response = $this->action("GET", "Api\\ClientApiController@get",
            $parameters = array('id' => $client->id),
            [],
            [],
            []);

        $content         = $response->getContent();
        $response_client = json_decode($content);

        $this->assertResponseStatus(200);
        $this->assertTrue($response_client->id === $client->id);
    }

    public function testGetByPage(){

        $response = $this->action("GET", "Api\\ClientApiController@getAll",
            $parameters = array('page' => 1,'per_page'=>10),
            [],
            [],
            []);

        $content         = $response->getContent();
        $this->assertResponseStatus(200);
        $list            = json_decode($content);
        $this->assertTrue(isset($list->total) && intval($list->total)>0);
    }

    public function testCreate(){

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $data = array(
            'user_id'            => $user->id,
            'app_name'           => 'test_app',
            'app_description'    => 'test app',
            'website'            => 'http://www.test.com',
            'application_type'   => IClient::ApplicationType_Native
        );

        $response = $this->action("POST", "Api\\ClientApiController@create",
            $data,
            [],
            [],
            []);

        $content       = $response->getContent();
        $json_response = json_decode($content);

        $this->assertResponseStatus(201);
        $this->assertTrue(isset($json_response->client_id) && !empty($json_response->client_id));
    }

}