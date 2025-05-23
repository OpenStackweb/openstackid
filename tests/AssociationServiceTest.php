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

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use OpenId\Services\OpenIdServiceCatalog;
use OpenId\Helpers\AssociationFactory;
use OpenId\OpenIdProtocol;
use Utils\Services\UtilsServiceCatalog;
use Utils\Exceptions\UnacquiredLockException;
use Mockery;
/**
 * Class AssociationServiceTest
 */
final class AssociationServiceTest extends BrowserKitTestCase
{

    public function tearDown():void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testAddPrivateAssociation()
    {

        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildPrivateAssociation(
            'https://www.test.com/', 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));
    }

    public function testAddSessionAssociation()
    {

        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildSessionAssociation(OpenIdProtocol::AssociationSessionTypeDHSHA256, 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));
    }

    public function testGetSessionAssociationRedisCrash()
    {

        $cache_mock = Mockery::mock(\Utils\Services\ICacheService::class);
        $cache_mock->shouldReceive('storeHash')->once();
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_mock);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildSessionAssociation(OpenIdProtocol::AssociationSessionTypeDHSHA256, 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));
        $hash = null;
        $cache_mock->shouldReceive('storeHash')->once()->andReturnUsing(function ($name, $values, $ttl) use (&$hash) {
            $hash = $values;
        });
        $cache_mock->shouldReceive('exists')->once()->andReturn(false);
        $cache_mock->shouldReceive('getHash')->once()->andReturnUsing(function ($name, $values) use (&$hash) {
            return $hash;
        });

        $res2 = $service->getAssociation($res->getHandle());

        $this->assertTrue(!is_null($res2));

        $this->assertTrue($res2->getSecret() === $res->getSecret());
    }

    public function testGetSessionAssociationMustFail_InvalidAssociation()
    {

        $this->expectException("OpenId\\Exceptions\\InvalidAssociation");

        $cache_mock = Mockery::mock(\Utils\Services\ICacheService::class);
        $cache_mock->shouldReceive('storeHash')->once();
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_mock);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $repo_mock = Mockery::mock(\OpenId\Repositories\IOpenIdAssociationRepository::class);
        $this->app->instance(\OpenId\Repositories\IOpenIdAssociationRepository::class, $repo_mock);
        $repo_mock->shouldReceive('add')->once();
        $repo_mock->shouldReceive('getByHandle')->once()->andReturnNull();

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildSessionAssociation(OpenIdProtocol::AssociationSessionTypeDHSHA256, 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));
        $hash = null;
        $cache_mock->shouldReceive('exists')->once()->andReturn(false);
        $service->getAssociation($res->getHandle());
    }


    public function testAddPrivateAssociationMustFail_ReplayAttackException()
    {

        $this->expectException("openid\\exceptions\\ReplayAttackException");

        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildPrivateAssociation('https://www.test.com/', 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));
        $lock_manager_service_mock->shouldReceive('acquireLock')->once()->andThrow(new UnacquiredLockException);
        $service->addAssociation($assoc);

    }


    public function testGetPrivateAssociation()
    {

        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->twice();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildPrivateAssociation('https://www.test.com/', 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));

        $res2 = $service->getAssociation($res->getHandle(), 'https://www.test.com/');

        $this->assertTrue(!is_null($res2));

        $this->assertTrue($res2->getSecret() === $res->getSecret());
    }


    public function testGetPrivateAssociationMustFail_OpenIdInvalidRealmException()
    {

        $this->expectException("OpenId\\Exceptions\\OpenIdInvalidRealmException");
        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildPrivateAssociation('https://www.test.com/', 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));

        $service->getAssociation($res->getHandle(), 'https://www1.test.com/');
    }

    public function testGetPrivateAssociationMustFail_InvalidAssociation()
    {

        $this->expectException("OpenId\\Exceptions\\InvalidAssociation");

        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->once();

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildPrivateAssociation('https://www.test.com/', 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));

        $service->getAssociation('123456', 'https://www1.test.com/');
    }

    public function testGetPrivateAssociationMustFail_ReplayAttackException()
    {

        $this->expectException("OpenId\\Exceptions\\ReplayAttackException");

        $cache_stub = new CacheServiceStub;
        $this->app->instance(UtilsServiceCatalog::CacheService, $cache_stub);

        $lock_manager_service_mock = Mockery::mock(\Utils\Services\ILockManagerService::class);
        $lock_manager_service_mock->shouldReceive('acquireLock')->times(2);

        $this->app->instance(UtilsServiceCatalog::LockManagerService, $lock_manager_service_mock);

        $service = $this->app[OpenIdServiceCatalog::AssociationService];
        $assoc = AssociationFactory::getInstance()->buildPrivateAssociation('https://www.test.com/', 3600);
        $res = $service->addAssociation($assoc);

        $this->assertTrue(!is_null($res));

        $res2 = $service->getAssociation($res->getHandle(), 'https://www.test.com/');

        $this->assertTrue(!is_null($res2));

        $this->assertTrue($res2->getSecret() === $res->getSecret());
        $lock_manager_service_mock->shouldReceive('acquireLock')->once()->andThrow(new UnacquiredLockException);
        $service->getAssociation($res->getHandle(), 'https://www.test.com/');
    }
} 