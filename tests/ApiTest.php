<?php
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
use Models\OAuth2\Api;
use Models\OAuth2\ResourceServer;
use Tests\BrowserKitTestCase;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class ApiTest
 */
final class ApiTest extends BrowserKitTestCase {


    private $current_realm;

    private $current_host;

    protected function prepareForTests()
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->current_realm = Config::get('app.url');
        $parts = parse_url($this->current_realm);
        $this->current_host = $parts['host'];
    }

    public function testGetById(){

        $repository = EntityManager::getRepository(Api::class);
        $api = $repository->findOneBy(['active'=>true]);

        $response = $this->action("GET", "Api\ApiController@get",
            $parameters = array('id' => $api->getId()),
            [],
            [],
            []);

        $content                  = $response->getContent();
        $response_api = json_decode($content);

        $this->assertResponseStatus(200);
        $this->assertTrue($response_api->id === $api->getId());
    }

    public function testGetAll(){

        $response = $this->action("GET", "Api\ApiController@getAll",
            $parameters = ['page' => 1,'per_page'=> 10],
            [],
            [],
            []);

        $content         = $response->getContent();
        $list            = json_decode($content);
        $this->assertTrue(isset($list->total) && intval($list->total)>0);
        $this->assertResponseStatus(200);
    }

    public function testCreate(){

        $repository      = EntityManager::getRepository(ResourceServer::class);
        $resource_server = $repository->findOneBy(['host'=> $this->current_host]);

        $data = [
            'name'               => 'test-api',
            'description'        => 'test api',
            'active'             => true,
            'resource_server_id' => $resource_server->getId(),
        ];

        $response = $this->action("POST", "Api\ApiController@create",
            $data,
            [],
            [],
            []);

        $content = $response->getContent();
        $json_response = json_decode($content);

        $this->assertResponseStatus(201);
        $this->assertTrue(isset($json_response->id) && !empty($json_response->id));
    }

    public function testDelete(){

        $repository      = EntityManager::getRepository(ResourceServer::class);
        $resource_server = $repository->findOneBy(['host'=> $this->current_host]);

        $data = array(
            'name'               => 'test-api',
            'description'        => 'test api',
            'active'             => true,
            'resource_server_id' => $resource_server->id,
        );

        $response = $this->action("POST", "Api\ApiController@create",
            $data,
            [],
            [],
            []);

        $content = $response->getContent();
        $json_response = json_decode($content);

        $this->assertResponseStatus(201);
        $this->assertTrue(isset($json_response->api_id) && !empty($json_response->api_id));

        $new_id = $json_response->api_id;
        $response = $this->action("DELETE", "Api\ApiController@delete",$parameters = array('id' => $new_id),
            [],
            [],
            []);

        $this->assertResponseStatus(204);

        $response = $this->action("GET", "Api\ApiController@get",
            $parameters = array('id' => $new_id),
            [],
            [],
            []);

        $content                  = $response->getContent();
        $response_api_endpoint    = json_decode($content);
        $this->assertResponseStatus(404);
    }

    public function testUpdate(){

        $resource_server = ResourceServer::where('host','=',$this->current_host)->first();

        $data = array(
            'name'               => 'test-api',
            'description'        => "test api",
            'active'             => true,
            'resource_server_id' => $resource_server->id,
        );

        $response = $this->action("POST", "Api\ApiController@create",
            $data,
            [],
            [],
            []);

        $content = $response->getContent();
        $json_response = json_decode($content);

        $this->assertResponseStatus(201);
        $this->assertTrue(isset($json_response->api_id) && !empty($json_response->api_id));

        $new_id = $json_response->api_id;
        //update it

        $data_update = array(
            'id'                => $new_id,
            'name'               => 'test-api-updated',
            'description'        => 'test api updated',
        );

        $response = $this->action("PUT", "Api\ApiController@update",$parameters = $data_update, [],
            [],
            []);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $this->assertResponseStatus(200);


        $response = $this->action("GET", "Api\ApiController@get",
            $parameters = array('id' =>$new_id),
            [],
            [],
            []);

        $content = $response->getContent();

        $updated_values = json_decode($content);

        $this->assertTrue($updated_values->name === 'test-api-updated');
        $this->assertResponseStatus(200);
    }

    public function testUpdateStatus(){

        $resource_server = ResourceServer::where('host','=',$this->current_host)->first();

        $data = array(
            'name'               => 'test-api',
            'description'        => 'test api',
            'active'             => true,
            'resource_server_id' => $resource_server->id,
        );

        $response = $this->action("POST", "Api\ApiController@create",$data);
	    $this->assertResponseStatus(201);

        $content = $response->getContent();
        $json_response = json_decode($content);

        $this->assertTrue(isset($json_response->api_id) && !empty($json_response->api_id));

        $new_id = $json_response->api_id;
        //update status

        $response = $this->action("PUT", "Api\ApiController@activate",array('id'     => $new_id));
	    $this->assertResponseStatus(200);

        $content = $response->getContent();

        $json_response = json_decode($content);

        $this->assertTrue($json_response==='ok');


        $response = $this->action("GET", "Api\ApiController@get",$parameters = array('id' => $new_id));

	    $this->assertResponseStatus(200);
        $content = $response->getContent();
	    $updated_values = json_decode($content);
        $this->assertTrue($updated_values->active == true);
    }

    public function testDeleteExisting(){

        $resource_server_api        = Api::where('name','=','resource-server')->first();

        $id = $resource_server_api->id;

        $response = $this->action("DELETE", "Api\ApiController@delete",$parameters = array('id' => $id),
            [],
            [],
            []);


        $this->assertResponseStatus(204);

        $response = $this->action("GET", "Api\ApiController@get",
            $parameters = array('id' => $id),
            [],
            [],
            []);

        $this->assertResponseStatus(404);
    }
}