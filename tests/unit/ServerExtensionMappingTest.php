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
use Models\OpenId\ServerExtension;
use Tests\BrowserKitTestCase;

/**
 * Class ServerExtensionMappingTest
 * @package Tests\unit
 */
class ServerExtensionMappingTest extends BrowserKitTestCase
{
    public function testServerExtensionPersistence()
    {
        $name = "TestExt";

        $ext = new ServerExtension();
        $ext->setName($name);
        $ext->setNamespace("TestExtNS");;
        $ext->setActive(true);
        $ext->setExtensionClass("TestExtClass");
        $ext->setDescription("Test description");
        $ext->setViewName("TestExtVN");

        EntityManager::persist($ext);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(ServerExtension::class);
        $found_ext = $repo->find($ext->getId());

        $this->assertInstanceOf(ServerExtension::class, $found_ext);
        $this->assertEquals($name, $found_ext->getName());
    }
}
