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
use Models\ServerConfiguration;
use Tests\BrowserKitTestCase;

/**
 * Class ServerConfigurationMappingTest
 * @package Tests\unit
 */
class ServerConfigurationMappingTest extends BrowserKitTestCase
{
    public function testServerConfigurationPersistence()
    {
        $key = 'test_key';
        $value = 'test_value';

        $conf = new ServerConfiguration();
        $conf->setKey($key);
        $conf->setValue($value);
        EntityManager::persist($conf);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(ServerConfiguration::class);
        $found_conf = $repo->find($conf->getId());

        $this->assertInstanceOf(ServerConfiguration::class, $found_conf);
        $this->assertEquals($key, $found_conf->getKey());
        $this->assertEquals($value, $found_conf->getValue());
    }
}
