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
use Illuminate\Support\Facades\Redis;
use Models\OAuth2\ServerPrivateKey;
use Models\UserExceptionTrail;
/**
 * Class DoctrineRepositoriesTests
 * @package Tests
 */
class DoctrineRepositoriesTests extends TestCase
{
    private $redis;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    public function setUp()
    {
        parent::setUp(); // Don't forget this!
        $this->redis = Redis::connection();
        $this->redis->flushall();
    }

    public function testServerPrivateKeyRepository(){
        $repository = EntityManager::getRepository(ServerPrivateKey::class);
        $keys = $repository->getActives();
        $this->assertTrue(!is_null($keys));
        $this->assertTrue(count($keys) > 0);
    }

    public function testUserExceptionTrailRepository(){
        $repository = EntityManager::getRepository(UserExceptionTrail::class);
        $ex = new UserExceptionTrail();
        $ex->setExceptionType("Auth\Exceptions\AuthenticationException");
        $ex->setFromIp("125.37.152.68");
        $ex->setCreatedAt(new \DateTime("now", new \DateTimeZone("UTC")));

        EntityManager::persist($ex);
        EntityManager::flush();

        $count = $repository->getCountByIPTypeOfLatestUserExceptions(
            "125.37.152.68",
            "Auth\Exceptions\AuthenticationException",
            10
        );

        $this->assertTrue($count >= 1);

        EntityManager::remove($ex);
        EntityManager::flush();
    }
}