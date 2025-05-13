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
use Models\OAuth2\ApiScope;
use Models\OAuth2\ApiScopeGroup;
use Tests\BrowserKitTestCase;

/**
 * Class ApiScopeGroupMappingTest
 * @package Tests\unit
 */
class ApiScopeGroupMappingTest extends BrowserKitTestCase
{
    public function testApiScopeGroupPersistence()
    {
        $name = 'test_group_name';

        $group = new ApiScopeGroup();
        $group->setName($name);
        $group->setActive(true);
        $group->setDescription('test description');

        $scope = new ApiScope();
        $scope->setName('test_scope_name');
        $scope->setShortDescription('test short description');
        $scope->setDescription('test description');
        $scope->setActive(true);
        EntityManager::persist($scope);

        $group->addScope($scope);

        EntityManager::persist($group);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(ApiScopeGroup::class);
        $found_group = $repo->find($group->getId());

        $this->assertInstanceOf(ApiScopeGroup::class, $found_group);
        $this->assertEquals($name, $found_group->getName());
        $this->assertCount(1, $found_group->getScopes()->toArray());
    }
}
