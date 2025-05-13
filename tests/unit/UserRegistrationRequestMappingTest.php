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

use App\libs\Auth\Models\UserRegistrationRequest;
use Auth\UserPasswordResetRequest;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;
use Auth\User;

/**
 * Class UserRegistrationRequestMappingTest
 * @package Tests\unit
 */
class UserRegistrationRequestMappingTest extends BrowserKitTestCase
{
    public function testUserRegistrationRequestPersistence()
    {
        $email = 'test@nomail.com';
        $f_name = 'First Name';
        $l_name = 'Last Name';
        $hash = 'TEST_HASH';

        $req = new UserRegistrationRequest();
        $req->setEmail($email);
        $req->setFirstName($f_name);
        $req->setLastName($l_name);;
        $req->setCompany("Company");
        $req->setCountryIsoCode("US");
        $req->setHash($hash);

        EntityManager::persist($req);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(UserRegistrationRequest::class);
        $found_req = $repo->find($req->getId());

        $this->assertInstanceOf(UserRegistrationRequest::class, $found_req);
        $this->assertEquals($email, $found_req->getEmail());
        $this->assertEquals($f_name, $found_req->getFirstName());
        $this->assertEquals($l_name, $found_req->getLastName());
        $this->assertEquals($hash, $found_req->getHash());
    }
}
