<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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

use App\Models\OAuth2\Factories\OTPFactory;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;
use OAuth2\Factories\OAuth2AuthorizationRequestFactory;
use OAuth2\OAuth2Message;
use OAuth2\OAuth2Protocol;
use Utils\Services\IdentifierGenerator;

/**
 * Class OTPModelTest
 * @package Tests
 */
class OTPModelTest extends BrowserKitTestCase
{
    /**
     * @var Client
     */
    static $aauth2_client;

    protected function setUp():void
    {
        parent::setUp();
    }

    protected function tearDown():void
    {
        parent::tearDown();
    }

    public function testCreateFromRequest(){

        $client_repository = EntityManager::getRepository(Client::class);

        $clients = $client_repository->findAll();

        $this->assertTrue(count($clients) > 0);

        $client = $clients[0];

        if(!$client instanceof Client) return;

        $client->enablePasswordless();
        $client->setOtpLifetime(60 * 3);
        $client->setOtpLength(6);

        EntityManager::persist($client);
        $values =
        [
            OAuth2Protocol::OAuth2Protocol_ClientId => $client->getClientId(),
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
            OAuth2Protocol::OAuth2Protocol_Scope => "test_scope",
            OAuth2Protocol::OAuth2Protocol_Nonce => "123456"
        ];

        $request = OAuth2AuthorizationRequestFactory::getInstance()->build
        (
            new OAuth2Message($values)
        );

        $this->assertTrue($request->isValid());
        $otp = OTPFactory::buildFromRequest($request, App::make(IdentifierGenerator::class), $client);
        EntityManager::persist($client);
        EntityManager::flush();
        $this->assertTrue($client->getOTPGrantsByEmailNotRedeemed("test@test.com")->count() > 0);
        $this->assertTrue(strlen($otp->getValue()) == $client->getOtpLength());
    }

    public function testCreateFromPayloadNoClient(){
        $payload =
            [
                OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
                OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
                OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
                OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
                OAuth2Protocol::OAuth2Protocol_Scope => "test_scope"
            ];

        $otp = OTPFactory::buildFromPayload($payload, App::make(IdentifierGenerator::class));

        EntityManager::persist($otp);
        EntityManager::flush();
        $this->assertTrue($otp->getId() > 0);
    }
}