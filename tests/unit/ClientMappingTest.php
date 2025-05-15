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
use Models\OAuth2\Client;
use Models\OAuth2\ResourceServer;
use Tests\BrowserKitTestCase;
use Auth\User;

/**
 * Class ClientMappingTest
 * @package Tests\unit
 */
class ClientMappingTest extends BrowserKitTestCase
{
    public function testClientPersistence()
    {
        $app_description = 'test app description';
        $host = 'https://www.openstack.org';

        $client_repo = EntityManager::getRepository(Client::class);
        $client = $client_repo->findAll()[0];

        $user_repo = EntityManager::getRepository(User::class);
        $user = $user_repo->findAll()[0];
        $admin_user1 = $user_repo->findAll()[1];
        $admin_user2 = $user_repo->findAll()[2];

        $rs = new ResourceServer();
        $rs->setFriendlyName('OpenStackId server 2');
        $rs->setHost($host);
        $rs->setIps('127.0.0.1');
        $rs->setActive(true);
        EntityManager::persist($rs);

        $client->setAppDescription($app_description);

        //Many-to-one mapping test
        $client->setEditedBy($user);

        //One-to-one mapping test
        $client->setResourceServer($rs);

        //Many-to-many mapping test
        $client->addAdminUser($admin_user1);
        $client->addAdminUser($admin_user2);

        EntityManager::persist($client);
        EntityManager::flush();
        EntityManager::clear();

        $found_client = $client_repo->find($client->getId());

        $this->assertEquals($app_description, $found_client->getApplicationDescription());
        $this->assertEquals($user->getEmail(), $found_client->getEditedByNice());
        $this->assertCount(2, $client->getAdminUsers()->toArray());
        $this->assertEquals($host, $found_client->getResourceServer()->getHost());
    }
}
