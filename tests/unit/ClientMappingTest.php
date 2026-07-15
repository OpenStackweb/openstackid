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

use DateTimeZone;
use jwa\JSONWebSignatureAndEncryptionAlgorithms;
use jwk\JSONWebKeyPublicKeyUseValues;
use jwk\JSONWebKeyTypes;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Api;
use Models\OAuth2\ApiScope;
use Models\OAuth2\Client;
use Models\OAuth2\ClientPublicKey;
use Models\OAuth2\OAuth2OTP;
use Models\OAuth2\ResourceServer;
use OAuth2\Models\IClient;
use Tests\BrowserKitTestCase;
use Auth\User;

/**
 * Class ClientMappingTest
 * @package Tests\unit
 */
class ClientMappingTest extends BrowserKitTestCase
{
    static $client_public_key_1 = <<<PPK
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAkjiUI6n3Fq140AipaLxN
IPCzEItQFcY8G5Xd17u7InM3H542+34PdBpwR66miQUgJK+rtfaot/v4QPj4/0Bn
Yc78BhI0Mp3tVEH95jjIrhDMZoRFfSQsAhiom5NTP1B5XiiyRjzkO1+7a29JST5t
IQUIS2U345DMWyf3GNlC1cBAfgI+PrRo3gLby/iW5EF/Mqq0ZUIOuggZ7r8kU2aU
hXILFx2w9V/y90DwruJdzZ0TesbsFit2nM3Axie7HX2wIpbl2hyvvhX/AxZ0NPud
Vh58wNogsKOMUN6guU+RzL5L6vF+QjfzBCtOE+CRmUD60E0LdQHzElBcF0tbc2cj
2YelZ0Dp+4NEBDjCNsSv//5hHacUxxXQdwwotLUV85iErEZgcGyMNnTMsw7JIh39
UBgOEmQgfpfOUlH+/5WmRO+kskvPCACz1SR8gzAKz9Nu9r3UyE+gWaZzM2+CpQ1s
zEd94MIapHxJw9vHogL7sNkjmZ34Y9eQmoCVevqDVpYEdTtLsg9H49+pEndQHI6l
GAB7QlsPLN8A17L2l3p68BFcYkSZR4GuXAyQguq3KzWYDZ9PjWAV5lhVg6K3GaV7
fvn2pKCk4P5Y5hZt08fholt3k/5Gc82CP6rfgQFi7HnpBJKRauoIdsvUPvXZYTLl
TaE5jLBAwxm+wF6Ue/nRPJMCAwEAAQ==
-----END PUBLIC KEY-----
PPK;

    public function testClientPersistence()
    {
        $app_description = 'test app description';
        $host = 'https://www.openstack.org';
        $otp_value = 'test_otp_value';

        $client_repo = EntityManager::getRepository(Client::class);
        $client = $client_repo->findAll()[0];

        $former_scopes_count = count($client->getClientScopes());
        $former_pks_count = count($client->getPublicKeys($otp_value));

        $user_repo = EntityManager::getRepository(User::class);
        $user = $user_repo->findAll()[0];
        $admin_user1 = $user_repo->findAll()[1];
        $admin_user2 = $user_repo->findAll()[2];

        $rs = new ResourceServer();
        $rs->setFriendlyName('OpenStackId server 2');
        $rs->setHost($host);
        $rs->setIps('127.0.0.1');
        $rs->setActive(true);
        EntityManager::persist($rs);

        $client->setAppDescription($app_description);

        //Many-to-one user mapping test
        $client->setEditedBy($user);

        //One-to-one resource server mapping test
        $client->setResourceServer($rs);

        //Many-to-many admin mapping test
        $client->addAdminUser($admin_user1);
        $client->addAdminUser($admin_user2);

        //One-to-many public key mapping test
        $now =  new \DateTime('now', new DateTimeZone('UTC'));
        $to   = new \DateTime('now', new DateTimeZone('UTC'));
        $to->add(new \DateInterval('P31D'));

        $pk = ClientPublicKey::buildFromPEM(
            'public_key_1',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Encryption,
            self::$client_public_key_1,
            JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
            true,
            $now,
            $to
        );
        $client->addPublicKey($pk);

        //Many-to-many scope mapping test
        $api = EntityManager::getRepository(Api::class)->findAll()[0];
        $scope = new ApiScope();
        $scope->setName('test_scope_name');
        $scope->setShortDescription('test short description');
        $scope->setDescription('test description');
        $scope->setActive(true);
        $scope->setApi($api);
        EntityManager::persist($scope);

        $client->addScope($scope);

        $otp_grant = new OAuth2OTP(6, 120);
        $otp_grant->setValue($otp_value);

        $client->addOTPGrant($otp_grant);

        EntityManager::persist($client);
        EntityManager::flush();
        EntityManager::clear();

        $found_client = $client_repo->find($client->getId());

        $this->assertEquals($app_description, $found_client->getApplicationDescription());
        $this->assertEquals($user->getEmail(), $found_client->getEditedByNice());
        $this->assertCount(2, $found_client->getAdminUsers()->toArray());
        $this->assertCount($former_scopes_count + 1, $found_client->getClientScopes());
        $this->assertEquals($host, $found_client->getResourceServer()->getHost());
        $this->assertTrue($found_client->hasOTP($otp_value));
        $this->assertCount($former_pks_count + 1, $found_client->getPublicKeys($otp_value));

        //Children removal tests

        $client = $client_repo->find($client->getId());
        $otp_grant = $client->getOTPByValue($otp_value);
        $client->removeOTPGrant($otp_grant);
        $client->removeAllAdminUsers();
        $client->removeAllScopes();

        EntityManager::persist($client);
        EntityManager::flush();
        EntityManager::clear();

        $found_client = $client_repo->find($client->getId());

        $this->assertFalse($found_client->hasOTP($otp_value));
        $this->assertEmpty($found_client->getAdminUsers()->toArray());
        $this->assertEmpty($found_client->getClientScopes());
    }

    public function testIsPostLogoutUriAllowedNativeClientAcceptsCustomScheme()
    {
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setPostLogoutRedirectUris('myapp://callback');

        $this->assertTrue($client->isPostLogoutUriAllowed('myapp://callback'));
        $this->assertFalse($client->isPostLogoutUriAllowed('otherapp://callback'));
    }

    public function testIsPostLogoutUriAllowedNativeClientMatchesCaseInsensitiveScheme()
    {
        // URI schemes are case-insensitive per RFC 3986. The write path (ClientFactory::populate) normally
        // lowercases the stored value, but a row can bypass that (same defense-in-depth rationale as the
        // dangerous-scheme and host-less-URI checks above) - the runtime match must not silently depend on it.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setPostLogoutRedirectUris('MyApp://Callback');

        $this->assertTrue($client->isPostLogoutUriAllowed('myapp://callback'));
    }

    public function testIsPostLogoutUriAllowedNonNativeClientRequiresHttps()
    {
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Web_App);
        $client->setPostLogoutRedirectUris('myapp://callback,https://www.test.com');

        $this->assertFalse($client->isPostLogoutUriAllowed('myapp://callback'));
        $this->assertTrue($client->isPostLogoutUriAllowed('https://www.test.com'));
    }

    public function testIsPostLogoutUriAllowedNativeClientRejectsHostlessUriWithoutError()
    {
        // host-less URIs (mailto:, file:///x, myapp:///cb) pass FILTER_VALIDATE_URL but have no authority;
        // for a native client the https guard is skipped, so this must not raise an undefined-array-key error.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setPostLogoutRedirectUris('myapp://callback');

        $this->assertFalse($client->isPostLogoutUriAllowed('mailto:foo@bar.com'));
        $this->assertFalse($client->isPostLogoutUriAllowed('file:///etc/passwd'));
        $this->assertFalse($client->isPostLogoutUriAllowed('myapp:///cb'));
    }

    public function testIsPostLogoutUriAllowedNativeClientRejectsDangerousSchemeEvenWhenWrittenDirectly()
    {
        // defense-in-depth: ClientService::assertNativeCustomSchemesAllowed() is the write-time gate, but a row
        // can reach storage through a path that bypasses ClientService entirely (e.g. ClientFactory::build()
        // called directly by a seeder). isPostLogoutUriAllowed() must independently reject dangerous schemes
        // at the runtime allow-gate, not rely solely on write-time validation having run.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setPostLogoutRedirectUris('javascript://x%0aalert(1),intent://scan/#Intent;end,myapp://callback');

        $this->assertFalse($client->isPostLogoutUriAllowed('javascript://x%0aalert(1)'));
        $this->assertFalse($client->isPostLogoutUriAllowed('intent://scan/#Intent;end'));
        $this->assertTrue($client->isPostLogoutUriAllowed('myapp://callback'));
    }

    public function testIsPostLogoutUriAllowedNativeClientRejectsPathSuffixOnRegisteredUri()
    {
        // same substring/prefix bypass fixed on isUriAllowed(): isPostLogoutUriAllowed() previously matched
        // only scheme://host[:port] as a substring of the whole registered CSV, ignoring path entirely - so
        // registering "myapp://callback/safe" also permitted "myapp://callback/other". The full path is now
        // part of the comparison.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setPostLogoutRedirectUris('myapp://callback/safe');

        $this->assertTrue($client->isPostLogoutUriAllowed('myapp://callback/safe'));
        $this->assertFalse($client->isPostLogoutUriAllowed('myapp://callback/other'));
        $this->assertFalse($client->isPostLogoutUriAllowed('myapp://callback/safe/extra'));
    }

    public function testIsPostLogoutUriAllowedNativeClientAcceptsDynamicQueryString()
    {
        // query strings are dynamic per logout request (session/state params) and were never part of the
        // registered value - canonicalUrl() drops them from both sides before comparison, so this must keep
        // working after the exact-match fix above.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setPostLogoutRedirectUris('myapp://callback/safe');

        $this->assertTrue($client->isPostLogoutUriAllowed('myapp://callback/safe?session=abc123&state=xyz'));
    }

    public function testIsUriAllowedNativeClientRejectsDangerousSchemeEvenWhenWrittenDirectly()
    {
        // same defense-in-depth as isPostLogoutUriAllowed, but for redirect_uris / isUriAllowed: the field
        // that actually carries the OAuth2 authorization code, and the more security-critical of the two.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setRedirectUris('javascript://x%0aalert(1),myapp://callback');

        $this->assertFalse($client->isUriAllowed('javascript://x%0aalert(1)'));
        $this->assertTrue($client->isUriAllowed('myapp://callback'));
    }

    public function testIsUriAllowedNativeClientAllowsHttpLoopbackButRejectsHttpElsewhere()
    {
        // RFC 8252 loopback interface redirection: http://127.0.0.1:{port}/... (or ::1 / localhost) is the
        // recommended pattern for native apps and was always allowed pre-existing (Native clients were fully
        // exempt from the https-required check). The dangerous-scheme deny-list must carve this out, or every
        // native app using the RFC-recommended pattern breaks the moment the deny-list includes 'http'.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setRedirectUris('http://127.0.0.1:51204/callback,http://localhost:8080/callback');

        $this->assertTrue($client->isUriAllowed('http://127.0.0.1:51204/callback'));
        $this->assertTrue($client->isUriAllowed('http://localhost:8080/callback'));
        $this->assertFalse($client->isUriAllowed('http://insecure.example.com/callback'));
    }

    public function testIsUriAllowedNativeClientRejectsHostlessUriWithoutError()
    {
        // canonicalUrl() had the same missing-host crash as isPostLogoutUriAllowed did before that fix;
        // isUriAllowed (used by the authorize/token/register/password-reset flows) must not crash either.
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setRedirectUris('myapp://callback');

        $this->assertFalse($client->isUriAllowed('mailto:foo@bar.com'));
        $this->assertFalse($client->isUriAllowed('file:///etc/passwd'));
    }

    public function testIsUriAllowedNativeClientRejectsPathSuffixOnRegisteredRedirectUri()
    {
        // isUriAllowed() previously matched via str_contains($uri, $redirect_uri) - a substring/prefix
        // check, not an exact match. Registering "myapp://callback/safe" therefore also permitted
        // "myapp://callback/other" and "myapp://callback/safe/extra": any path appended after the
        // registered value passed. redirect_uris carries the OAuth2 authorization code, so an exact
        // match is required here (query strings remain tolerated - canonicalUrl() strips them from
        // both sides before comparison).
        $client = new Client();
        $client->setApplicationType(IClient::ApplicationType_Native);
        $client->setRedirectUris('myapp://callback/safe');

        $this->assertTrue($client->isUriAllowed('myapp://callback/safe'));
        $this->assertFalse($client->isUriAllowed('myapp://callback/other'));
        $this->assertFalse($client->isUriAllowed('myapp://callback/safe/extra'));
    }
}
