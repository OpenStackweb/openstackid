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
 * limitations under the License.**/
use App\Http\Utils\IUserIPHelperProvider;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Illuminate\Support\Facades\Redis;
use Models\OAuth2\Client;
use OAuth2\Services\IUserConsentService;
use Illuminate\Support\Facades\App;
use OpenId\Exceptions\ReplayAttackException;
use OpenId\Services\IServerExtensionsService;
use Mockery;
/**
 * Class ServicesTests
 * @package Tests
 */
final class ServicesTests extends TestCase
{
    public function tearDown():void
    {
        parent::tearDown();
        Mockery::close();
    }

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

    public function testServerExtensionsService(){
        $service = App::make(IServerExtensionsService::class);
        $this->assertTrue($service instanceof IServerExtensionsService);
        $extensions = $service->getAllActiveExtensions();

        $this->assertTrue(!is_null($extensions));
        $this->assertTrue(count($extensions) > 0);
    }

}