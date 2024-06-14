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
use Models\OAuth2\ResourceServer;
use OAuth2\OAuth2Protocol;
use utils\factories\BasicJWTFactory;
use Utils\Services\IAuthService;
use Utils\Services\UtilsServiceCatalog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
/**
 * Class OIDCPasswordlessTest
 * @package Tests
 */
class OIDCPasswordlessTestCase extends OpenStackIDBaseTestCase
{

    /**
     * @var Client
     */
    public static $client = null;

    /**
     * @var ResourceServer
     */
    public static $resource_server = null;

    public static $client2 = null;

    protected function setUp(): void
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

        self::$client2 = $client_repository->findOneBy(['client_id' => '1234/Vcvr6fvQbH4HyNgwKlfSpkce.openstack.client']);
        self::$client2->enablePasswordless();
        self::$client2->setOtpLifetime(60 * 3);
        self::$client2->setOtpLength(6);
        EntityManager::persist(self::$client2);

        $resource_server_repository = EntityManager::getRepository(ResourceServer::class);

        self::$resource_server = $resource_server_repository->findOneBy([
            'friendly_name' => 'test resource server'
        ]);

    }

    protected function tearDown():void
    {
        parent::tearDown();
    }

    /**
     * @var string
     */
    private $current_realm;

    protected function prepareForTests():void
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
        $this->assertTrue(!is_null($otp1));
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

    public function testCodeInlineFlow()
    {

        $scope = sprintf('%s profile email address %s',
            OAuth2Protocol::OpenIdConnect_Scope,
            OAuth2Protocol::OfflineAccess_Scope
        );

        $params = [
            'client_id' => self::$client->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionInline,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            ["HTTP_Authorization" => " Basic " . base64_encode(self::$resource_server->getClient()->getClientId() . ':' . self::$resource_server->getClient()->getClientSecret())]
        );

        $this->assertResponseStatus(200);

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        $otp = $otp_response->value;
        // exchange
        $params = [
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_Passwordless,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionEmail,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
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

        $this->assertTrue($response->error === 'invalid_grant');
    }

    public function testCodeInlineFlowComplete()
    {

        $scope = sprintf('%s profile email address %s',
            OAuth2Protocol::OpenIdConnect_Scope,
            OAuth2Protocol::OfflineAccess_Scope
        );

        $params = [
            'client_id' => self::$client2->getClientId(),
            'scope' => $scope,
            OAuth2Protocol::OAuth2Protocol_Nonce => '123456',
            OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_OTP,
            OAuth2Protocol::OAuth2PasswordlessConnection => OAuth2Protocol::OAuth2PasswordlessConnectionInline,
            OAuth2Protocol::OAuth2PasswordlessSend => OAuth2Protocol::OAuth2PasswordlessSendCode,
            OAuth2Protocol::OAuth2PasswordlessEmail => "test@test.com",
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            ["HTTP_Authorization" => " Basic " . base64_encode(self::$resource_server->getClient()->getClientId() . ':' . self::$resource_server->getClient()->getClientSecret())]
        );

        $this->assertResponseStatus(200);

        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));

        $content = $response->getContent();

        $otp_response = json_decode($content);

        $this->assertTrue($otp_response->scope == $scope);

        $otp = $otp_response->value;

        // OIDC flow

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);
        $code_verifier = "1qaz2wsx3edc4rfv5tgb6yhn7ujm8ik8ik9ol1qaz2wsx3edc4rfv5tgb6yhn~";
        $encoded = base64_encode(hash('sha256', $code_verifier, true));

        $codeChallenge = strtr(rtrim($encoded, '='), '+/', '-_');

        $params = [
            'client_id' => self::$client2->getClientId(),
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            'response_mode' => 'fragment',
            'scope' => $scope,
            'state' => '123456',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            OAuth2Protocol::OAuth2Protocol_LoginHint => "test@test.com",
            OAuth2Protocol::OAuth2Protocol_OTP_LoginHint => $otp,
            OAuth2Protocol::OAuth2Protocol_Prompt => OAuth2Protocol::OAuth2Protocol_Prompt_Consent,
        ];

        $response = $this->action("GET", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(302);
        $url = $response->getTargetUrl();
        // get auth code ...
        $comps = @parse_url($url);
        $fragment = $comps['fragment'];
        $response = [];
        parse_str($fragment, $response);

        $this->assertTrue(isset($response['code']));
        $this->assertTrue(isset($response['state']));
        $this->assertTrue($response['state'] === '123456');

        $params = [
            'code' => $response['code'],
            'redirect_uri' => 'https://www.test.com/oauth2',
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            'code_verifier' => $code_verifier,
            'client_id' => self::$client2->getClientId(),
        ];

        $response = $this->action
        (
            "POST",
            "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            []
        );

        $this->assertResponseStatus(200);
        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
        $content = $response->getContent();

        $response = json_decode($content);
        $access_token = $response->access_token;

        $this->assertTrue(!empty($access_token));

        $refresh_token = $response->refresh_token;

        $this->assertTrue(!empty($refresh_token));

        $id_token = $response->id_token;

        $this->assertTrue(!empty($id_token));


        $params = [
            'refresh_token' => $refresh_token,
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_RefreshToken,
            'client_id' => self::$client2->getClientId()
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            []);

        $this->assertResponseStatus(200);
        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
        $content = $response->getContent();

        $response = json_decode($content);

        //get new access token and new refresh token...
        $new_access_token = $response->access_token;
        $new_refresh_token = $response->refresh_token;
        $new_id_token = $response->id_token;

        $this->assertTrue(!empty($new_access_token));
        $this->assertTrue(!empty($new_refresh_token));
        $this->assertTrue(!empty($new_id_token));

    }

    public function testInvalidRedeemCodeEmailFlow()
    {

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
        $this->assertTrue($otp->isValid());
    }

}