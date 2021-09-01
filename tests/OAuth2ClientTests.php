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
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;
use Illuminate\Support\Facades\Redis;
/**
 * Class OAuth2ClientTests
 * @package Tests
 */
final class OAuth2ClientTests extends TestCase
{

    private $redis;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    public function setUp():void
    {
        parent::setUp(); // Don't forget this!
        $this->redis = Redis::connection();
        $this->redis->flushall();
    }

    public function testGetClient($appName = 'oauth2_test_app'): Client
    {
        $repo = EntityManager::getRepository(Client::class);
        $client = $repo->getByApplicationName($appName);
        $this->assertTrue(!is_null($client));
        $this->assertTrue($client->getApplicationName() == $appName);
        $accessTokens = $client->getValidAccessTokens();
        $this->assertTrue(count($accessTokens) == 0);

        return $client;
    }
}