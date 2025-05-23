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
use Models\OAuth2\ResourceServer;
use Illuminate\Support\Facades\Config;
use Auth\User;
use Illuminate\Support\Facades\Session;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class ResourceServerApiTest
 * Test ResourceServer REST API
 */
final class ResourceServerApiTest extends BrowserKitTestCase
{

    private $current_realm;

    private $current_host;

    protected function prepareForTests():void
    {
        parent::prepareForTests();
        //Route::enableFilters();
        $this->current_realm = Config::get('app.url');
        $parts = parse_url($this->current_realm);
        $this->current_host = $parts['host'];
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $this->be($user);
        Session::start();
    }

    public function testGetById()
    {

        $resource_server = EntityManager::getRepository(ResourceServer::class)->findOneBy(['host' => $this->current_host]);

        $response = $this->action("GET", "Api\\ApiResourceServerController@get",
            $parameters = array('id' => $resource_server->id),
            [],
            [],
            []);

        $content = $response->getContent();
        $response_resource_server = json_decode($content);

        $this->assertResponseStatus(200);
        $this->assertTrue($response_resource_server->id === $resource_server->id);
    }

    public function testGetByPage()
    {

        $response = $this->action("GET", "Api\\ApiResourceServerController@getAll",
            $parameters = array('page' => 1, 'per_page' => 10),
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $list = json_decode($content);
        $this->assertTrue(isset($list->total) && intval($list->total) > 0);
    }

    public function testCreate()
    {

        $data = array(
            'host' => 'www.resource.server.2.test.com',
            'ips' => '10.0.0.1',
            'friendly_name' => 'Resource Server 2',
            'active' => true,
        );

        $response = $this->action("POST", "Api\\ApiResourceServerController@create",
            $data,
            [],
            [],
            []);
        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $json_response = json_decode($content);
        $this->assertTrue(isset($json_response->id));
        $this->assertTrue(!empty($json_response->id));

    }

    public function testRegenerateClientSecret()
    {

        $data = array(
            'host' => 'www.resource.server.3.test.com',
            'ips' => '10.0.0.2',
            'friendly_name' => 'Resource Server 3',
            'active' => true,
        );

        $response = $this->action("POST", "Api\\ApiResourceServerController@create",
            $data,
            [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $new_id = $json_response->id;

        $response = $this->action(
            "GET",
            "Api\\ApiResourceServerController@get",
            $parameters = [
                'id' => $new_id,
                'expand' => 'client',
            ],
            [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);


        $client_secret = $json_response->client->client_secret;

        $response = $this->action("PUT", "Api\\ApiResourceServerController@regenerateClientSecret",
            $parameters = array('id' => $new_id),
            [],
            [],
            []);


        $content = $response->getContent();

        $json_response = json_decode($content);

        $new_secret = $json_response->client_secret;

        $this->assertTrue(!empty($new_secret));
        $this->assertTrue($new_secret !== $client_secret);

        $this->assertResponseStatus(201);

    }

    public function testDelete()
    {

        $data = array(
            'host' => 'www.resource.server.4.test.com',
            'ips' => '10.0.0.4',
            'friendly_name' => 'Resource Server 4',
            'active' => true,
        );


        $response = $this->action("POST", "Api\\ApiResourceServerController@create",
            $parameters = $data,
            [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $new_id = $json_response->id;

        $response = $this->action("DELETE", "Api\\ApiResourceServerController@delete", $parameters = array('id' => $new_id),
            [],
            [],
            []);

        $this->assertResponseStatus(204);


        $response = $this->action("GET", "Api\\ApiResourceServerController@get", $parameters = array('id' => $new_id),
            [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $this->assertResponseStatus(404);

    }

    public function testDeleteExistingOne()
    {

        $resource_server = EntityManager::getRepository(ResourceServer::class)->findOneBy(['host' => $this->current_host]);

        $new_id = $resource_server->id;

        $response = $this->action("DELETE", "Api\\ApiResourceServerController@delete", $parameters = array('id' => $new_id),
            [],
            [],
            []);


        $this->assertResponseStatus(204);


        $response = $this->action("GET", "Api\\ApiResourceServerController@get", $parameters = array('id' => $new_id),
            [],
            [],
            []);

        $this->assertResponseStatus(404);

    }

    public function testUpdate()
    {

        $data = array(
            'host' => 'www.resource.server.5.test.com',
            'ips' => '10.0.0.8',
            'friendly_name' => 'Resource Server 5',
            'active' => true,
        );

        $response = $this->action("POST", "Api\\ApiResourceServerController@create", $parameters = $data,
            [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $new_id = $json_response->id;

        $data_update = array(
            'id' => $new_id,
            'host' => 'www.resource.server.5.test.com',
            'ips' => '127.0.0.2',
            'friendly_name' => 'Resource Server 6',
        );

        $response = $this->action("PUT", "Api\\ApiResourceServerController@update", $parameters = $data_update, [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $this->assertResponseStatus(201);

        $response = $this->action("GET", "Api\\ApiResourceServerController@get", $parameters = array('id' => $new_id),
            [],
            [],
            []);

        $content = $response->getContent();

        $updated_values = json_decode($content);

        $this->assertTrue($updated_values->ips === '127.0.0.2');
        $this->assertTrue($updated_values->friendly_name === 'Resource Server 6');
        $this->assertResponseStatus(200);
    }

    public function testUpdateStatus()
    {

        $data = array(
            'host' => 'www.resource.server.7.test.com',
            'ips' => '127.0.0.8',
            'friendly_name' => 'Resource Server 7',
            'active' => true,
        );

        $response = $this->action("POST", "Api\\ApiResourceServerController@create", $data);
        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $json_response = json_decode($content);
        $new_id = $json_response->id;
        $response = $this->action("DELETE", "Api\\ApiResourceServerController@deactivate", array('id' => $new_id));
        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $json_response = json_decode($content);
        $response = $this->action("GET", "Api\\ApiResourceServerController@get", $parameters = array('id' => $new_id));
        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $updated_values = json_decode($content);
        $this->assertTrue($updated_values->active === false);
    }

}