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
}
