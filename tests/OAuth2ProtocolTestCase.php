<?php namespace Tests;
/**
 * Copyright 2016 OpenStack Foundation
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
use Auth\User;
use OAuth2\OAuth2Protocol;
use Utils\Services\IAuthService;
use Utils\Services\UtilsServiceCatalog;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Illuminate\Support\Facades\DB;
/**
 * Class OAuth2ProtocolTest
 * Test Suite for OAuth2 Protocol
 */
final class OAuth2ProtocolTestCase extends OpenStackIDBaseTestCase
{

    private $current_realm;

    protected function prepareForTests():void
    {
        parent::prepareForTests();
        $this->current_realm = Config::get('app.url');
        $user_repository = EntityManager::getRepository(User::class);
        $this->user      = $user_repository->findOneBy(['email' => 'sebastian@tipit.net']);
        Session::start();
        $this->be($this->user);
    }

    public function createApplication()
    {
        $app = parent::createApplication();
        $app->singleton(UtilsServiceCatalog::ServerConfigurationService, StubServerConfigurationService::class);
        return $app;
    }

    /**
     * Get Auth Code Test
     */
    public function testAuthCode()
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => 'https://www.test.com:443/oauth2?param=1&BackUrl=123344',
            'response_type' => 'code',
            'scope'         => sprintf('%s/resource-server/read', $this->current_realm),
        ];

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(302);

        $url = $response->getTargetUrl();

        $consent_response = $this->call('POST', $url, array(
            'trust' => 'AllowOnce',
            '_token' => Session::token()
        ));

        $this->assertResponseStatus(302);

        $auth_response = $this->action("GET", "OAuth2\OAuth2ProviderController@auth",
            [],
            [],
            [],
            []);

        $this->assertResponseStatus(302);

        $url = $auth_response->getTargetUrl();

        $comps = @parse_url($url);
        $query = $comps['query'];
        $output = [];
        parse_str($query, $output);

        $this->assertTrue(array_key_exists('code', $output));
        $this->assertTrue(!empty($output['code']));

    }

    /**
     * Get Auth Code Test
     */
    public function testAuthCodeNoResponseType()
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => '',
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(400);

    }

    /**
     * Get Auth Code Test
     */
    public function testCancelAuthCode()
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => 'code',
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(302);

        $url = $response->getTargetUrl();

        $consent_response = $this->call('POST', $url, array(
            'trust'  => IAuthService::AuthorizationResponse_DenyOnce,
            '_token' => Session::token()
        ));

        $this->assertResponseStatus(302);
    }

    public function testAuthCodeInvalidRedirectUri()
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/invalid_uri',
            'response_type' => 'code',
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(400);

        $body = $response->getContent();

        $this->assertTrue(str_contains($body, 'redirect_uri_mismatch'));
    }

    /** Get Token Test
     * @throws Exception
     */
    public function testToken($test_refresh_token = true)
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
        $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';

        $params = array
        (
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
        );

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []
        );

        $status = $response->getStatusCode();
        $url = $response->getTargetUrl();
        $content = $response->getContent();

        $comps = @parse_url($url);
        $query = $comps['query'];
        $output = [];
        parse_str($query, $output);

        $params = array(
            'code' => $output['code'],
            'redirect_uri' => 'https://www.test.com/oauth2',
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
        );

        $response = $this->action
        (
            "POST",
            "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony internally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret))
        );

        $status = $response->getStatusCode();

        $this->assertResponseStatus(200);
        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
        $content = $response->getContent();

        $response = json_decode($content);
        $access_token = $response->access_token;

        $this->assertTrue(!empty($access_token));

        if($test_refresh_token){
            $refresh_token = $response->refresh_token;
            $this->assertTrue(!empty($refresh_token));
        }
    }

    public function testTokenNTimes($n = 100){

        for($i=0; $i< $n ;$i++){
            $this->testToken($i === 0);
        }
    }

    /** Get Token Test
     * @throws Exception
     */
    public function testAuthCodeReplayAttack()
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
        $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';

        $params = array
        (
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
        );

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $status = $response->getStatusCode();
        $url = $response->getTargetUrl();
        $content = $response->getContent();

        $comps = @parse_url($url);
        $query = $comps['query'];
        $output = [];
        parse_str($query, $output);

        $params = array(
            'code' => $output['code'],
            'redirect_uri' => 'https://www.test.com/oauth2',
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
        );


        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

        $status = $response->getStatusCode();

        $this->assertResponseStatus(200);
        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
        $content = $response->getContent();

        $response = json_decode($content);
        $access_token = $response->access_token;
        $refresh_token = $response->refresh_token;

        $this->assertTrue(!empty($access_token));
        $this->assertTrue(!empty($refresh_token));

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

        $this->assertResponseStatus(400);
    }

    /** test validate token grant
     * @throws Exception
     */
    public function testValidateToken()
    {

        try {

            $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
            $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';

            Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

            //do authorization ...

            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => 'https://www.test.com/oauth2',
                'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
                OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
                'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
                $params,
                [],
                [],
                []);

            $status = $response->getStatusCode();
            $url = $response->getTargetUrl();
            $content = $response->getContent();

            // get auth code ...
            $comps = @parse_url($url);
            $query = $comps['query'];
            $output = [];
            parse_str($query, $output);


            //do get auth token...
            $params = array(
                'code' => $output['code'],
                'redirect_uri' => 'https://www.test.com/oauth2',
                'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);
            $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
            $content = $response->getContent();

            $response = json_decode($content);
            //get access token and refresh token...
            $access_token = $response->access_token;
            $refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($access_token));
            $this->assertTrue(!empty($refresh_token));

            //do token validation ....
            $params = array(
                'token' => $access_token,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@introspection",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);
            $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
            $content = $response->getContent();

            $response = json_decode($content);
            $validate_access_token = $response->access_token;
            //old token and new token should be equal
            $this->assertTrue(!empty($validate_access_token));
            $this->assertTrue($validate_access_token === $access_token);
            return $access_token;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function testResourceServerIntrospection()
    {
        $access_token = $this->testValidateToken();

        $client_id = 'resource.server.1.openstack.client';
        $client_secret = '123456789123456789123456789123456789123456789';
        //do token validation ....
        $params = array(
            'token' => $access_token,
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@introspection",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));
        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
        $response = json_decode($content);
        $validate_access_token = $response->access_token;
        //old token and new token should be equal
        $this->assertTrue(!empty($validate_access_token));
        $this->assertTrue($validate_access_token === $access_token);
    }

    public function testResourceServerIntrospectionNotValidIP()
    {
        $access_token = $this->testValidateToken();

        $client_id = 'resource.server.2.openstack.client';
        $client_secret = '123456789123456789123456789123456789123456789';
        //do token validation ....
        $params = array(
            'token' => $access_token,
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@introspection",
            $params,
            [],
            [],
            [],
            // Symfony interally prefixes headers with "HTTP", so
            array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

        $this->assertResponseStatus(400);
        $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        $response = json_decode($content);
    }

    /** test validate token grant
     * @throws Exception
     */
    public function testValidateExpiredToken()
    {

        try {
            // set token lifetime
            $_ENV['access.token.lifetime'] = 1;

            $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
            $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';

            Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

            //do authorization ...

            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => 'https://www.test.com/oauth2',
                'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
                OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
                'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
                $params,
                [],
                [],
                []);

            $status = $response->getStatusCode();
            $url = $response->getTargetUrl();
            $content = $response->getContent();

            // get auth code ...
            $comps = @parse_url($url);
            $query = $comps['query'];
            $output = [];
            parse_str($query, $output);


            //do get auth token...
            $params = array(
                'code' => $output['code'],
                'redirect_uri' => 'https://www.test.com/oauth2',
                'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);

            $content = $response->getContent();

            $response = json_decode($content);
            //get access token and refresh token...
            $access_token = $response->access_token;
            $refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($access_token));
            $this->assertTrue(!empty($refresh_token));
            sleep(2);
            //do token validation ....
            $params = array(
                'token' => $access_token,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@introspection",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(400);

            $content = $response->getContent();

            $response = json_decode($content);

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@introspection",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(400);

            $content = $response->getContent();

            $response = json_decode($content);

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /** test refresh token grant
     * @throws Exception
     */
    public function testRefreshToken()
    {
        try {

            $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
            $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';


            Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

            //do authorization ...

            $params = array(
                OAuth2Protocol::OAuth2Protocol_ClientId => $client_id,
                OAuth2Protocol::OAuth2Protocol_RedirectUri => 'https://www.test.com/oauth2',
                OAuth2Protocol::OAuth2Protocol_ResponseType => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
                OAuth2Protocol::OAuth2Protocol_Scope => sprintf('%s/resource-server/read', $this->current_realm),
                OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
                $params,
                [],
                [],
                []);

            $status = $response->getStatusCode();
            $url = $response->getTargetUrl();
            $content = $response->getContent();

            // get auth code ...
            $comps = @parse_url($url);
            $query = $comps['query'];
            $output = [];
            parse_str($query, $output);

            //do get auth token...
            $params = array(
                'code' => $output['code'],
                'redirect_uri' => 'https://www.test.com/oauth2',
                'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);
            $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
            $content = $response->getContent();

            $response = json_decode($content);
            //get access token and refresh token...
            $access_token = $response->access_token;
            $refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($access_token));
            $this->assertTrue(!empty($refresh_token));

            $params = array(
                'refresh_token' => $refresh_token,
                'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_RefreshToken,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);
            $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
            $content = $response->getContent();

            $response = json_decode($content);

            //get new access token and new refresh token...
            $new_access_token  = $response->access_token;
            $new_refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($new_access_token));
            $this->assertTrue(!empty($new_refresh_token));


            //do token validation ....
            $params = array(
                'token' => $new_access_token,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@introspection",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * test refresh token replay attack
     * @throws Exception
     */
    public function testRefreshTokenReplayAttack()
    {
        try {

            $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
            $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';

            Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

            //do authorization ...

            $params = [
                'client_id'                               => $client_id,
                'redirect_uri'                            => 'https://www.test.com/oauth2',
                'response_type'                           => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
                'scope'                                   => sprintf('%s/resource-server/read', $this->current_realm),
                OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline
            ];

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
                $params,
                [],
                [],
                []);

            $status  = $response->getStatusCode();
            $url     = $response->getTargetUrl();
            $content = $response->getContent();

            // get auth code ...
            $comps  = @parse_url($url);
            $query  = $comps['query'];
            $output = [];

            parse_str($query, $output);

            //do get auth token...
            $params = [
                'code'         => $output['code'],
                'redirect_uri' => 'https://www.test.com/oauth2',
                'grant_type'   => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            ];

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);

            $content       = $response->getContent();

            $response      = json_decode($content);
            //get access token and refresh token...
            $access_token  = $response->access_token;
            $refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($access_token));
            $this->assertTrue(!empty($refresh_token));

            $params = [
                'refresh_token' => $refresh_token,
                'grant_type'    => OAuth2Protocol::OAuth2Protocol_GrantType_RefreshToken,
            ];

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);
            $this->assertEquals('application/json;charset=UTF-8', $response->headers->get('Content-Type'));
            $content = $response->getContent();

            $response = json_decode($content);

            //get new access token and new refresh token...
            $new_access_token  = $response->access_token;
            $new_refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($new_access_token));
            $this->assertTrue(!empty($new_refresh_token));

            //do re refresh and we will get a 400 http error ...
            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(400);

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * test refresh token replay attack
     * @throws Exception
     */
    public function testRefreshTokenDeleted()
    {
        try {

            $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
            $client_secret = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';

            Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

            //do authorization ...

            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => 'https://www.test.com/oauth2',
                'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
                'scope' => sprintf('%s/resource-server/read', $this->current_realm),
                OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
                $params,
                [],
                [],
                []);

            $status = $response->getStatusCode();
            $url = $response->getTargetUrl();
            $content = $response->getContent();

            // get auth code ...
            $comps = @parse_url($url);
            $query = $comps['query'];
            $output = [];
            parse_str($query, $output);


            //do get auth token...
            $params = array(
                'code' => $output['code'],
                'redirect_uri' => 'https://www.test.com/oauth2',
                'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_AuthCode,
            );


            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));
            $this->assertResponseStatus(200);

            $content = $response->getContent();

            $response = json_decode($content);
            //get access token and refresh token...
            $access_token = $response->access_token;
            $refresh_token = $response->refresh_token;

            $this->assertTrue(!empty($access_token));
            $this->assertTrue(!empty($refresh_token));

            // delete from DB ...

            DB::table('oauth2_refresh_token')->delete();

            $params = array(
                'refresh_token' => $refresh_token,
                'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_RefreshToken,
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(400);

        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function testImplicitFlow()
    {

        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSyQ3x.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Token,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            'state' => '123456'
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
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

        $this->assertTrue(isset($response['access_token']) && !empty($response['access_token']));
        $this->assertTrue(isset($response['expires_in']));
        $this->assertTrue(isset($response['scope']));
        $this->assertTrue(isset($response['state']));
        $this->assertTrue($response['state'] === '123456');
        $this->assertTrue(isset($response['token_type']));
        $this->assertTrue($response['token_type'] === 'Bearer');

    }

    public function testClientCredentialsFlow()
    {
        try {

            $client_id = '11z87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
            $client_secret = '11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg';

            //do get auth token...
            $params = array(
                OAuth2Protocol::OAuth2Protocol_GrantType => OAuth2Protocol::OAuth2Protocol_GrantType_ClientCredentials,
                OAuth2Protocol::OAuth2Protocol_Scope => sprintf('%s/resource-server/read', $this->current_realm),
            );

            $response = $this->action("POST", "OAuth2\OAuth2ProviderController@token",
                $params,
                [],
                [],
                [],
                // Symfony interally prefixes headers with "HTTP", so
                array("HTTP_Authorization" => " Basic " . base64_encode($client_id . ':' . $client_secret)));

            $this->assertResponseStatus(200);

            $content = $response->getContent();

            $response = json_decode($content);

            $this->assertTrue(!empty($response->access_token));

        } catch (Exception $ex) {
            throw $ex;
        }

    }

    public function testMissingScope()
    {

        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => 'code',
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(302);

        $url = $response->getTargetUrl();

        $comps = @parse_url($url);

        $this->assertTrue(isset($comps["query"]));
        $this->assertTrue($comps["query"] == "error=invalid_scope&error_description=missing+scope+param");
    }

    public function testAuthCodePKCEPublicClient(){
        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSpkce.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);
        $code_verifier = "1qaz2wsx3edc4rfv5tgb6yhn7ujm8ik8ik9ol1qaz2wsx3edc4rfv5tgb6yhn~";
        $encoded = base64_encode(hash('sha256', $code_verifier, true));

        $codeChallenge = strtr(rtrim($encoded, '='), '+/', '-_');

        $params = [
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            'response_mode' => 'fragment',
            'scope' => sprintf('openid %s/resource-server/read', $this->current_realm),
            'state' => '123456',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
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
            'client_id' => $client_id,
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

        $params = [
            'refresh_token' => $refresh_token,
            'grant_type' => OAuth2Protocol::OAuth2Protocol_GrantType_RefreshToken,
            'client_id' => $client_id
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
        $new_access_token  = $response->access_token;
        $new_refresh_token = $response->refresh_token;

        $this->assertTrue(!empty($new_access_token));
        $this->assertTrue(!empty($new_refresh_token));

    }

    public function testAuthCodeInvalidPKCEPublicClient(){
        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSpkce.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);
        $code_verifier = "1qaz2wsx3edc4rfv5tgb6yhn7ujm8ik8ik9ol1qaz2wsx3edc4rfv5tgb6yhn~";
        $encoded = base64_encode(hash('sha256', $code_verifier, true));

        $codeChallenge = strtr(rtrim($encoded, '='), '+/', '-_');

        $params = [
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Code,
            'response_mode' => 'fragment',
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            'state' => '123456',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
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
            'code_verifier' => "missmatch",
            'client_id' => $client_id,
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

        $this->assertResponseStatus(400);

    }

    public function testTokenRevocation()
    {
        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSyQ3x.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Token,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
            'state' => '123456'
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
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

        $this->assertTrue(isset($response['access_token']) && !empty($response['access_token']));
        $this->assertTrue(isset($response['expires_in']));
        $this->assertTrue(isset($response['scope']));
        $this->assertTrue(isset($response['state']));
        $this->assertTrue($response['state'] === '123456');
        $this->assertTrue(isset($response['token_type']));
        $this->assertTrue($response['token_type'] === 'Bearer');

        $params = array(
            OAuth2Protocol::OAuth2Protocol_Token => $response['access_token'],
            OAuth2Protocol::OAuth2Protocol_TokenType_Hint => OAuth2Protocol::OAuth2Protocol_AccessToken,
            OAuth2Protocol::OAuth2Protocol_ClientId => $client_id
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@revoke",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);

    }

    public function testTokenRevocationInvalidClient()
    {
        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSyQ3x.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Token,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
            'state' => '123456'
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
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

        $this->assertTrue(isset($response['access_token']) && !empty($response['access_token']));
        $this->assertTrue(isset($response['expires_in']));
        $this->assertTrue(isset($response['scope']));
        $this->assertTrue(isset($response['state']));
        $this->assertTrue($response['state'] === '123456');
        $this->assertTrue(isset($response['token_type']));
        $this->assertTrue($response['token_type'] === 'Bearer');

        //set another public client
        $client_id = 'Jiz87D8/Vcvr6fvQbH4HyNgwKlfSyQ2x.openstack.client';
        $params = array(
            OAuth2Protocol::OAuth2Protocol_Token => $response['access_token'],
            OAuth2Protocol::OAuth2Protocol_TokenType_Hint => OAuth2Protocol::OAuth2Protocol_AccessToken,
            OAuth2Protocol::OAuth2Protocol_ClientId => $client_id
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@revoke",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
    }

    public function testTokenRevocationInvalidHint()
    {

        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSyQ3x.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Token,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
            'state' => '123456'
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
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

        $this->assertTrue(isset($response['access_token']) && !empty($response['access_token']));
        $this->assertTrue(isset($response['expires_in']));
        $this->assertTrue(isset($response['scope']));
        $this->assertTrue(isset($response['state']));
        $this->assertTrue($response['state'] === '123456');
        $this->assertTrue(isset($response['token_type']));
        $this->assertTrue($response['token_type'] === 'Bearer');


        $params = array(
            OAuth2Protocol::OAuth2Protocol_Token => $response['access_token'],
            OAuth2Protocol::OAuth2Protocol_TokenType_Hint => OAuth2Protocol::OAuth2Protocol_RefreshToken,
            OAuth2Protocol::OAuth2Protocol_ClientId => $client_id
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@revoke",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);

    }

    public function testTokenRevocationInvalidToken()
    {

        $client_id = '1234/Vcvr6fvQbH4HyNgwKlfSyQ3x.openstack.client';

        Session::put("openid.authorization.response", IAuthService::AuthorizationResponse_AllowOnce);

        $params = array(
            'client_id' => $client_id,
            'redirect_uri' => 'https://www.test.com/oauth2',
            'response_type' => OAuth2Protocol::OAuth2Protocol_ResponseType_Token,
            'scope' => sprintf('%s/resource-server/read', $this->current_realm),
            OAuth2Protocol::OAuth2Protocol_AccessType => OAuth2Protocol::OAuth2Protocol_AccessType_Offline,
            'state' => '123456'
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@auth",
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

        $this->assertTrue(isset($response['access_token']) && !empty($response['access_token']));
        $this->assertTrue(isset($response['expires_in']));
        $this->assertTrue(isset($response['scope']));
        $this->assertTrue(isset($response['state']));
        $this->assertTrue($response['state'] === '123456');
        $this->assertTrue(isset($response['token_type']));
        $this->assertTrue($response['token_type'] === 'Bearer');


        $params = array(
            OAuth2Protocol::OAuth2Protocol_Token => '12345678910',
            OAuth2Protocol::OAuth2Protocol_ClientId => $client_id
        );

        $response = $this->action("POST", "OAuth2\OAuth2ProviderController@revoke",
            $params,
            [],
            [],
            []);

        $this->assertResponseStatus(200);
    }
}