<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
use Auth\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class UserApiTest
 * @package Tests
 */
class UserApiTest extends BrowserKitTestCase
{

    private $current_realm;

    private $current_host;

    protected function prepareForTests()
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->current_realm = Config::get('app.url');
        $parts = parse_url($this->current_realm);
        $this->current_host = $parts['host'];

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $this->be($user);
        Session::start();
    }

    public function testCreate()
    {
        $data = [
            'first_name' => 'test',
            'last_name' => 'test',
            'email' => 'test+1@test.com',
            'country_iso_code' => 'US',
            'password' => '1qaz2wsx!',
            'password_confirmation' => '1qaz2wsx!',
            'active' => true,
            'email_verified' => true,
        ];

        $response = $this->action("POST", "Api\\UserApiController@create",
            $data,
            [],
            [],
            []);

        $content = $response->getContent();
        $json_response = json_decode($content);

        $this->assertResponseStatus(201);
    }

}