<?php namespace Tests\unit;

/**
 * Copyright 2025 OpenStack Foundation
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
use Models\OAuth2\Api;
use Models\OAuth2\ApiEndpoint;
use Models\OAuth2\ApiScope;
use Tests\BrowserKitTestCase;

/**
 * Class ApiEndpointMappingTest
 * @package Tests\unit
 */
class ApiEndpointMappingTest extends BrowserKitTestCase
{
    public function testApiEndpointPersistence()
    {
        $name = 'test_endpoint_name';
        $route = '/api/test_endpoint';
        $scope_name = 'openid';

        $api = EntityManager::getRepository(Api::class)->findAll()[0];

        $scope = new ApiScope();
        $scope->setName($scope_name);
        $scope->setShortDescription('test short description');
        $scope->setDescription('test description');
        $scope->setActive(true);
        EntityManager::persist($scope);

        $endpoint = new ApiEndpoint();
        $endpoint->setName($name);
        $endpoint->setRoute($route);
        $endpoint->setHttpMethod('GET');
        $endpoint->setStatus(true);
        $endpoint->setAllowCors(true);
        $endpoint->setAllowCredentials(true);
        $endpoint->setApi($api);
        $endpoint->addScope($scope);

        EntityManager::persist($endpoint);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(ApiEndpoint::class);
        $found_endpoint = $repo->find($endpoint->getId());
        $found_scope = $found_endpoint->getScopes()[0];

        $this->assertInstanceOf(ApiEndpoint::class, $found_endpoint);
        $this->assertInstanceOf(ApiScope::class, $found_scope);
        $this->assertEquals($name, $found_endpoint->getName());
        $this->assertEquals($route, $found_endpoint->getRoute());
        $this->assertEquals($scope_name, $found_scope->getName());
    }
}
