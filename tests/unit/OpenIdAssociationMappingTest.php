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

use Illuminate\Support\Facades\Date;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\BannedIP;
use Models\OpenId\OpenIdAssociation;
use Models\WhiteListedIP;
use Tests\BrowserKitTestCase;

/**
 * Class OpenIdAssociationMappingTest
 * @package Tests\unit
 */
class OpenIdAssociationMappingTest extends BrowserKitTestCase
{
    public function testOpenIdAssociationPersistence()
    {
        $identifier = "TestIdentifier";

        $assoc = new OpenIdAssociation();
        $assoc->setIdentifier($identifier);
        $assoc->setSecret("TestSecret");;
        $assoc->setType(1);
        $assoc->setMacFunction("TestMacFunction");
        $assoc->setLifetime(600);
        $assoc->setIssued(Date::now());

        EntityManager::persist($assoc);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(OpenIdAssociation::class);
        $found_assoc = $repo->findOneBy(["identifier" => $assoc->getIdentifier()]);

        $this->assertInstanceOf(OpenIdAssociation::class, $found_assoc);
        $this->assertEquals($identifier, $found_assoc->getIdentifier());
    }
}
