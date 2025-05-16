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

use Auth\UserPasswordResetRequest;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;
use Auth\User;

/**
 * Class UserPasswordResetRequestMappingTest
 * @package Tests\unit
 */
class UserPasswordResetRequestMappingTest extends BrowserKitTestCase
{
    public function testUserPasswordResetRequestPersistence()
    {
        $user_repo = EntityManager::getRepository(User::class);
        $user = $user_repo->findAll()[0];

        $req = new UserPasswordResetRequest();
        $req->setOwner($user);
        $req->setResetLink('https://www.openstack.org/reset/link');
        $req->generateToken();

        EntityManager::persist($req);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(UserPasswordResetRequest::class);
        $found_req = $repo->find($req->getId());

        $this->assertInstanceOf(UserPasswordResetRequest::class, $found_req);
        $this->assertEquals($user->getEmail(), $found_req->getOwner()->getEmail());
    }
}
