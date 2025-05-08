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
use jwk\JSONWebKeyTypes;
use jwk\JSONWebKeyPublicKeyUseValues;
use Models\OAuth2\Client;
use jwa\JSONWebSignatureAndEncryptionAlgorithms;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Auth\User;
use Illuminate\Support\Facades\Config;
use TestKeys;
/**
 * Class ClientPublicKeyApiTest
 */
class ClientPublicKeyApiTest extends BrowserKitTestCase {

    private $current_realm;

    private $current_host;

    protected function prepareForTests():void
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->current_realm = Config::get('app.url');
        $parts = parse_url($this->current_realm);
        $this->current_host = $parts['host'];
        //already logged user
        $user_repository = EntityManager::getRepository(User::class);
        $user = $user_repository->findOneBy(["identifier" => 'sebastian.marcet']);
        $this->be($user, 'web');
    }

    public function testCreate(){

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
        $client = EntityManager::getRepository(Client::class)->findOneBy(['client_id' => $client_id]);

        $data =
        [
            'kid'         => 'test key',
            'pem_content' => TestKeys::$public_key2_pem,
            'usage'       => JSONWebKeyPublicKeyUseValues::Signature,
            'type'        => JSONWebKeyTypes::RSA,
            'active'      => false,
            'valid_from'  => '01/01/2016',
            'valid_to'    => '01/15/2016',
            'alg'         => JSONWebSignatureAndEncryptionAlgorithms::RS512,
        ];

        $response = $this->action("POST", "Api\\ClientPublicKeyApiController@_create",
            $wildcards = ['id' => $client->id],
            $data,
            [],
            []);

        $content       = $response->getContent();
        $json_response = json_decode($content);
        $this->assertResponseStatus(201);
        $this->assertTrue(isset($json_response->id) && !empty($json_response->id));

        $public_key = $client->getPublicKeyByIdentifier('test key');

        $this->assertTrue(!is_null($public_key) && $json_response->id === $public_key->getId());
    }
}