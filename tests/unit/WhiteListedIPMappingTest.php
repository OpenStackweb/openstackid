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
use Models\BannedIP;
use Models\WhiteListedIP;
use Tests\BrowserKitTestCase;

/**
 * Class WhiteListedIPMappingTest
 * @package Tests\unit
 */
class WhiteListedIPMappingTest extends BrowserKitTestCase
{
    public function testWhiteListedIPPersistence()
    {
        $ip = "127.0.0.1";
        $wl_ip = new WhiteListedIP();
        $wl_ip->setIp("127.0.0.1");

        EntityManager::persist($wl_ip);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(WhiteListedIP::class);
        $found_wl_ip = $repo->find($wl_ip->getId());

        $this->assertInstanceOf(WhiteListedIP::class, $found_wl_ip);
        $this->assertEquals($ip, $found_wl_ip->getIp());
    }
}
