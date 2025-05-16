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

use Auth\User;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\BannedIP;
use Tests\BrowserKitTestCase;

/**
 * Class BannedIPMappingTest
 * @package Tests\unit
 */
class BannedIPMappingTest extends BrowserKitTestCase
{
    public function testBannedIPPersistence()
    {
        $ip = "127.0.0.1";

        $user = EntityManager::getRepository(User::class)->findAll()[0];

        $banned_ip = new BannedIP();
        $banned_ip->setIp("127.0.0.1");
        $banned_ip->setExceptionType("TestExceptionType");
        $banned_ip->setHits(1);

        //Many-to-one relation with User
        $banned_ip->setUser($user);

        EntityManager::persist($banned_ip);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(BannedIP::class);
        $found_banned_ip = $repo->find($banned_ip->getId());

        $this->assertInstanceOf(BannedIP::class, $found_banned_ip);
        $this->assertEquals($ip, $found_banned_ip->getIp());
        $this->assertEquals($user->getEmail(), $found_banned_ip->getUser()->getEmail());
    }
}
