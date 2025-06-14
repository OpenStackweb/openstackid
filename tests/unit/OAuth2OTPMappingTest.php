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
use Models\OAuth2\OAuth2OTP;
use Tests\BrowserKitTestCase;

/**
 * Class OAuth2OTPMappingTest
 * @package Tests\unit
 */
class OAuth2OTPMappingTest extends BrowserKitTestCase
{
    public function testOAuth2OTPPersistence()
    {
        $email = 'test@nomail.com';
        $connection = 'email';

        $otp = new OAuth2OTP(6, 0);
        $otp->setConnection($connection);
        $otp->setSend('1');
        $otp->setValue('test value');
        $otp->setNonce('test nonce');
        $otp->setRedirectUrl('https://www.openstack.org/');
        $otp->setScope('openid email');
        $otp->setEmail($email);
        $otp->setPhoneNumber('1234567890');

        //Many-to-one client mapping test
        $client = EntityManager::getRepository(Client::class)->findAll()[0];
        $otp->setClient($client);

        EntityManager::persist($otp);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(OAuth2OTP::class);
        $found_otp = $repo->find($otp->getId());

        $this->assertInstanceOf(OAuth2OTP::class, $found_otp);
        $this->assertEquals($connection, $found_otp->getConnection());
        $this->assertEquals($email, $found_otp->getEmail());
        $this->assertEquals($client->getApplicationName(), $found_otp->getClient()->getApplicationName());
    }
}
