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
use Models\OAuth2\Client;
use Models\OAuth2\ResourceServer;
use Tests\BrowserKitTestCase;

/**
 * Class ResourceServerMappingTest
 * @package Tests\unit
 */
class ResourceServerMappingTest extends BrowserKitTestCase
{
    public function testClientPersistence()
    {
        $host = 'https://www.openstack.org';

        $client = EntityManager::getRepository(Client::class)->findAll()[0];
        $api = EntityManager::getRepository(Api::class)->findAll()[0];

        $rs = new ResourceServer();
        $rs->setFriendlyName('OpenStackId server 2');
        $rs->setHost($host);
        $rs->setIps('127.0.0.1');
        $rs->setActive(true);

        //One-to-one client mapping test
        $rs->setClient($client);

        //One-to-many api mapping test
        $rs->addApi($api);

        EntityManager::persist($rs);
        EntityManager::flush();
        EntityManager::clear();

        $rs_repo = EntityManager::getRepository(ResourceServer::class);
        $found_rs = $rs_repo->find($rs->getId());

        $this->assertCount(1, $found_rs->getApis()->toArray());
        $this->assertEquals($host, $found_rs->getHost());
        $this->assertEquals($client->getApplicationName(), $found_rs->getClient()->getApplicationName());

        //Children removal tests
        $rs = $rs_repo->find($rs->getId());
        $apis = $rs->getApis();

        foreach ($apis as $api) {
            $rs->removeApi($api);
        }

        EntityManager::persist($rs);
        EntityManager::flush();
        EntityManager::clear();

        $found_rs = $rs_repo->find($rs->getId());
        $this->assertEmpty($found_rs->getApis()->toArray());
    }
}
