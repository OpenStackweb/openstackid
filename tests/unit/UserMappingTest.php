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
use Models\OpenId\OpenIdTrustedSite;
use Models\UserAction;
use Tests\BrowserKitTestCase;
use models\oauth2\UserConsent;
use Auth\User;
use Utils\Services\IAuthService;

/**
 * Class UserMappingTest
 * @package Tests\unit
 */
class UserMappingTest extends BrowserKitTestCase
{
    public function testUserPersistence()
    {
        $email = 'test@nomail.com';
        $realm = 'https://www.test.com/';

        $user = new User();
        $user->setEmail($email);
        $user->setPassword('P@sswordS3cret');

        $user_action = new UserAction();
        $user_action->setFromIp("127.0.0.1");
        $user_action->setUserAction("test action");;
        $user_action->setOwner($user);

        $user->addUserAction($user_action);

        $site = new OpenIdTrustedSite();
        $site->setRealm($realm);
        $site->setPolicy(IAuthService::AuthorizationResponse_AllowForever);
        $site->setOwner($user);
        $site->setData(json_encode([]));
        $user->addTrustedSite($site);

        EntityManager::persist($user);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(User::class);
        $found_user = $repo->find($user->getId());
        $found_trusted_site = $found_user->getTrustedSites()->first();

        $this->assertInstanceOf(User::class, $found_user);
        $this->assertEquals($email, $found_user->getEmail());
        $this->assertCount(1, $user->getActions()->toArray());
        $this->assertEquals($realm, $found_trusted_site->getRealm());;
    }
}
