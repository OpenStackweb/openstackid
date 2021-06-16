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
use App\Mail\OAuth2PasswordlessOTPMail;
use Illuminate\Support\Facades\Mail;
use jwe\IJWE;
use jwk\impl\RSAJWKFactory;
use jwk\JSONWebKeyPublicKeyUseValues;
use jws\IJWS;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use OAuth2\OAuth2Protocol;
use utils\factories\BasicJWTFactory;
use Utils\Services\UtilsServiceCatalog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
/**
 * Class OIDCPasswordlessTest
 * @package Tests
 */
class OIDCPasswordlessTest extends OpenStackIDBaseTest
{

    /**
     * @var Client
     */
    public static $client = null;

    protected function setUp():void
    {
        parent::setUp();

        $client_repository = EntityManager::getRepository(Client::class);

        $clients = $client_repository->findAll();


        self::$client = $clients[0];

        self::$client->enablePasswordless();
        self::$client->setOtpLifetime(60 * 3);
        self::$client->setOtpLength(6);
        self::$client->setTokenEndpointAuthMethod(OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic);
        EntityManager::persist(self::$client);
    }

    protected function tearDown():void
    {
        parent::tearDown();
    }

    /**
     * @var string
     */
    private $current_realm;

    protected function prepareForTests()
    {
        parent::prepareForTests();
        App::singleton(UtilsServiceCatalog::ServerConfigurationService, StubServerConfigurationService::class);
        $this->current_realm = Config::get('app.url');
        Session::start();
    }

    public function testCodeEmailFlowErrorScopes(){
        $scope = sprintf('%s profile email',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp){
            $otp = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        // ask for wider scopes
        $scope = sprintf('%s profile email address',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp,
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(400);
        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue(!empty($response->error));
    }

    public function testCodeEmailFlowError(){
        $scope = sprintf('%s profile email',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendLink,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(400);
    }

    public function testCodeEmailFlowNoRefreshToken(){
        $scope = sprintf('%s profile email address',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp){
            $otp = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp,
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(200);

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $response = json_decode($content);
        $access_token = $response->access_token;
        $id_token = $response->id_token;

        $this->assertTrue(!empty($access_token));
        $this->assertTrue(!property_exists($response, "refresh_token"));
        $this->assertTrue(!empty($id_token));
    }

    public function testCodeEmailFlowConsecutiveOTP(){
        $scope = sprintf('%s profile email address',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp1 = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp1){
            $otp1 = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp2 = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp2){
            $otp2 = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        $repository = EntityManager::getRepository(OAuth2OTP::class);

        $otp1 = $repository->getByValue($otp1);
        $this->assertTrue(is_null($otp1));
        $otp2 = $repository->getByValue($otp2);
        $this->assertTrue(!is_null($otp2));
    }

    public function testCodeEmailFlowNarrowScopes(){
        $scope = sprintf('%s profile email address',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp){
            $otp = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        // ask for wider scopes
        $scope = sprintf('%s profile email',
            OAuth2Protocol::OpenIdConnect_Scope,
        );

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp,
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $response = json_decode($content);
        $this->assertTrue(!empty($response->id_token));
    }

    public function testCodeEmailFlow() {

        $scope = sprintf('%s profile email address %s',
            OAuth2Protocol::OpenIdConnect_Scope,
            OAuth2Protocol::OfflineAccess_Scope
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp){
            $otp = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp,
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(200);

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $response = json_decode($content);
        $access_token = $response->access_token;
        $refresh_token = $response->refresh_token;
        $id_token = $response->id_token;

        $this->assertTrue(!empty($access_token));
        $this->assertTrue(!empty($refresh_token));
        $this->assertTrue(!empty($id_token));
    }

    public function testInvalidRedeemCodeEmailFlow() {

        $scope = sprintf('%s profile email address %s',
            OAuth2Protocol::OpenIdConnect_Scope,
            OAuth2Protocol::OfflineAccess_Scope
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $otp = null;
        Mail::assertNotQueued(OAuth2PasswordlessOTPMail::class, function(OAuth2PasswordlessOTPMail $email) use(&$otp){
            $otp = $email->otp;
        });

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp.'1',
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(400);

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp.'2',
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(400);

        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail =>"test@test.com",
            OAuth2Protocol::OAuth2Protocol_ResponseType_OTP => $otp.'3',
            OAuth2Protocol::OAuth2Protocol_Scope => $scope
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode(self::$client->getClientId() . ':' . self::$client->getClientSecret())));

        $this->assertResponseStatus(400);

        $repository = EntityManager::getRepository(OAuth2OTP::class);

        $otp = $repository->getByValue($otp);
        $this->assertTrue(!is_null($otp));
        $this->assertTrue(!$otp->isValid());
    }

}