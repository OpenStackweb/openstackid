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
use Tests\BrowserKitTestCase;
use models\oauth2\UserConsent;
use Auth\User;

/**
 * Class UserConsentMappingTest
 * @package Tests\unit
 */
class UserConsentMappingTest extends BrowserKitTestCase
{
    public function testUserConsentPersistence()
    {
        $email = 'test@nomail.com';
        $scopes = 'openid email';

        $user = new User();
        $user->setEmail($email);
        $user->setPassword('P@sswordS3cret');

        $repo = EntityManager::getRepository(Client::class);
        $client = $repo->findAll()[0];

        $consent = new UserConsent();
        $consent->setScope($scopes);
        $consent->setOwner($user);
        $consent->setClient($client);

        EntityManager::persist($user);
        EntityManager::persist($consent);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(User::class);
        $found_user = $repo->find($user->getId());
        $found_consent = $found_user->findFirstConsentByClientAndScopes($client, $scopes);

        $this->assertInstanceOf(UserConsent::class, $found_consent);
        $this->assertEquals($scopes, $found_consent->getScope());
        $this->assertEquals($email, $found_consent->getOwner()->getEmail());
    }
}
