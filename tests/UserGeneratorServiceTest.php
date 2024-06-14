<?php namespace Tests;
/**
 * Copyright 2015 OpenStack Foundation
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
use Auth\UserNameGeneratorService;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Auth\User;
/**
 * Class UserGeneratorServiceTest
 */
final class UserGeneratorServiceTest extends BrowserKitTestCase {


    public function testBuildUsers()
    {

        $user_name_service_generator = new UserNameGeneratorService();

        $member1 = EntityManager::getRepository(User::class)->findOneBy( ['email' => "mkiss@tipit.net"]);
        $member2 = EntityManager::getRepository(User::class)->findOneBy( ['email' => "fujg573@tipit.net"]);
        $member3 = EntityManager::getRepository(User::class)->findOneBy( ['email' => "mrbharathee@tipit.net"]);
        $member4 = EntityManager::getRepository(User::class)->findOneBy( ['email' => "yuanying@tipit.net"]);

        $member1 = $user_name_service_generator->generate($member1);
        $this->assertTrue($member1->getIdentifier() === 'marton.kiss');

        $member2 = $user_name_service_generator->generate($member2);
        $this->assertTrue($member2->getIdentifier() === 'fujg573');

        $member3 = $user_name_service_generator->generate($member3);
        $this->assertTrue($member3->getIdentifier() === 'bharath.kumar.m.r');

        $member4 = $user_name_service_generator->generate($member4);
        $this->assertTrue($member4->getIdentifier() === 'yuanying');

    }

}