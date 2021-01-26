<?php namespace Database\Seeders;
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
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;
use Auth\User;
use Utils\Services\IAuthService;
use Models\OAuth2\Client;
use Models\OAuth2\ResourceServer;
use Models\OAuth2\ApiScope;
use Models\OAuth2\Api;
use Models\OAuth2\ApiEndpoint;
use \jwk\JSONWebKeyPublicKeyUseValues;
use \jwk\JSONWebKeyTypes;
use \jwa\JSONWebSignatureAndEncryptionAlgorithms;
use Illuminate\Database\Seeder;
use Models\OAuth2\ClientPublicKey;
use Models\OAuth2\ServerPrivateKey;
use OpenId\Extensions\Implementations\OpenIdAXExtension;
use OpenId\Extensions\Implementations\OpenIdSREGExtension;
use OpenId\Extensions\Implementations\OpenIdOAuth2Extension;
use OpenId\Extensions\Implementations\OpenIdSREGExtension_1_0;
use LaravelDoctrine\ORM\Facades\EntityManager;
use App\libs\Auth\Factories\UserFactory;
use App\libs\Auth\Factories\GroupFactory;
use OAuth2\Models\IOAuth2User;
use OpenId\Models\IOpenIdUser;
use Auth\Group;
use App\Models\Factories\ServerConfigurationFactory;
use App\Models\OpenId\Factories\ServerExtensionFactory;
use App\Models\OAuth2\Factories\ResourceServerFactory;
use Illuminate\Support\Facades\Config;
use App\Models\OAuth2\Factories\ApiFactory;
use App\Models\OAuth2\Factories\ApiScopeFactory;
use App\Models\OAuth2\Factories\ClientFactory;
use App\Models\OpenId\Factories\OpenIdTrustedSiteFactory;
use App\Models\OAuth2\Factories\ApiEndpointFactory;
use App\libs\OAuth2\IUserScopes;
use App\libs\Auth\Models\IGroupSlugs;
use Illuminate\Support\Facades\DB;
use DateTimeZone;
use TestKeys;
/**
 * Class OAuth2ApplicationSeeder
 * This seeder is only for testing purposes
 */
final class TestSeeder extends Seeder {

static $client_private_key_1 = <<<PPK
-----BEGIN RSA PRIVATE KEY-----
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
-----END RSA PRIVATE KEY-----
PPK;

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

static $client_private_key_2 = <<<PPK
-----BEGIN RSA PRIVATE KEY-----
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
-----END RSA PRIVATE KEY-----
PPK;

static $client_public_key_2 = <<<PPK
-----BEGIN PUBLIC KEY-----
MIICITANBgkqhkiG9w0BAQEFAAOCAg4AMIICCQKCAgBHhlSRoBMo9uYJyHPr0g54
EzzKrpjNBUDqnTztggsIfXR3A73TolGmeXTECu+QIAyEtGGDylp4cJhyworIwzdA
fMCY9Xux5B+Vo0Kyte2JMvwzanNLhiT21rVw56ZfyxkCJKUxz3wba0kIWQyW+kvw
LhvbQzmexHnQs15qsfP4o2MEFVTCH2ohQ57OJ8BOSU8XfddCWommmFAQcQwGXYh9
woky4NOpGBqbnGBXgWF2rnbD0GYL1Sd3OSrgTHG3WrG95UizOcV9uijI8vWxczVl
P7sriaY7Xetcbh+Z5AbAf9TMOuccRFaM/KlovR7sOcQDO1NzqlL/PRzCfzcNId22
Q6uV3QE2hzRKfd+lKI1YmFrVJ0SnWlSdeX7kkWn/+eQ4WfkK9hmv4/0bzQYo1XCo
pEpjefZoWiErCAt4nEt2wr6f6BmSjTGVajGlhn7q8wwBBHfjAUfCgIAk8uGNGWke
gvytTYywLnyNEF8tHcdg+We+W6itqJ5bQNFHa9uxX+haURoTOcGqxN8n2LPcLoLU
7xqZY23wpeXO7anzqheGSQUHb56rWqcCRQ9jF/1RLCyyVd9eOEZF6Ke53qpLQxib
hqnZfba0zOKqhbxod6RSOswSF3wwHoIj+6SRNKgui4DDcMMc+bsndr/KUDxaYpiu
In2KVE6kd5a0t8BHgdu/yQIDAQAB
-----END PUBLIC KEY-----
PPK;

    private function createTestGroups(){
        $groups_payloads = [
            [
                'name' => IOAuth2User::OAuth2ServerAdminGroup,
                'slug' => IOAuth2User::OAuth2ServerAdminGroup,
            ],
            [
                'name' => IOAuth2User::OAuth2SystemScopeAdminGroup,
                'slug' => IOAuth2User::OAuth2SystemScopeAdminGroup,
            ],
            [
                'name' => IOpenIdUser::OpenIdServerAdminGroup,
                'slug' => IOpenIdUser::OpenIdServerAdminGroup,
            ],
            [
                'name' => IGroupSlugs::SuperAdminGroup,
                'slug' => IGroupSlugs::SuperAdminGroup,
            ],
            [
                'name' => IGroupSlugs::RawUsersGroup,
                'slug' => IGroupSlugs::RawUsersGroup,
            ]
        ];

        foreach ($groups_payloads as $payload){
            $group = GroupFactory::build($payload);
            EntityManager::persist($group);
        }
        EntityManager::flush();
    }

    private function createTestUsers(){
        $group_repository = EntityManager::getRepository(Group::class);

        $oauth2_admin_group        = $group_repository->findOneBy(['slug' => IOAuth2User::OAuth2ServerAdminGroup]);
        $opendid_admin_group       = $group_repository->findOneBy(['slug' => IOpenIdUser::OpenIdServerAdminGroup,]);
        $system_scopes_admin_group = $group_repository->findOneBy(['slug' => IOAuth2User::OAuth2SystemScopeAdminGroup]);
        $super_admin_group         = $group_repository->findOneBy(['slug' => IGroupSlugs::SuperAdminGroup]);
        $raw_users_group           = $group_repository->findOneBy(['slug' => IGroupSlugs::RawUsersGroup]);

        $user_payloads = [
           [
                'first_name' => 'Sebastian',
                'last_name' => 'Marcet',
                'email' => 'sebastian@tipit.net',
                'password' => '1qaz2wsx',
                'password_enc' => \Auth\AuthHelper::AlgSHA1_V2_4,
                'gender' => 'male',
                'address1' => 'Av. Siempre Viva 111',
                'address2' => 'Av. Siempre Viva 111',
                'city' => 'Lanus Este',
                'state' => 'Buenos Aires',
                'post_code' => '1824',
                'country' => 'AR',
                'language' => 'ESP',
                'active' => true,
                'email_verified' => true,
                'groups' => [
                    $super_admin_group
                ],
               'identifier' => '1',
            ],
            [
                'first_name' => 'Márton',
                'last_name' => 'Kiss',
                'email' => 'mkiss@tipit.net',
                'password' => '1qaz2wsx',
                'password_enc' => \Auth\AuthHelper::AlgSHA1_V2_4,
                'gender' => 'male',
                'address1' => 'Av. Siempre Viva 111',
                'address2' => 'Av. Siempre Viva 111',
                'city' => 'Lanus Este',
                'state' => 'Buenos Aires',
                'post_code' => '1824',
                'country' => 'AR',
                'language' => 'ESP',
                'active' => true,
                'email_verified' => true,
                'groups' => [
                    $super_admin_group
                ],
                'identifier' => '2',
            ],
            [
                'first_name' => '付',
                'last_name' => '金刚',
                'email' => 'fujg573@tipit.net',
                'password' => '1qaz2wsx',
                'password_enc' => \Auth\AuthHelper::AlgSHA1_V2_4,
                'gender' => 'male',
                'address1' => 'Av. Siempre Viva 111',
                'address2' => 'Av. Siempre Viva 111',
                'city' => 'Lanus Este',
                'state' => 'Buenos Aires',
                'post_code' => '1824',
                'country' => 'AR',
                'language' => 'ESP',
                'active' => true,
                'email_verified' => true,
                'groups' => [
                    $super_admin_group
                ],
                'identifier' => '3',
            ],
            [
                'first_name' => 'Bharath',
                'last_name' => 'Kumar M R',
                'email' => 'mrbharathee@tipit.net',
                'password' => '1qaz2wsx',
                'password_enc' => \Auth\AuthHelper::AlgSHA1_V2_4,
                'gender' => 'male',
                'address1' => 'Av. Siempre Viva 111',
                'address2' => 'Av. Siempre Viva 111',
                'city' => 'Lanus Este',
                'state' => 'Buenos Aires',
                'post_code' => '1824',
                'country' => 'AR',
                'language' => 'ESP',
                'active' => true,
                'email_verified' => true,
                'groups' => [
                    $super_admin_group
                ],
                'identifier' => '4',
            ],
            [
                'first_name' => '大塚',
                'last_name' => '元央',
                'email' => 'yuanying@tipit.net',
                'password' => '1qaz2wsx',
                'password_enc' => \Auth\AuthHelper::AlgSHA1_V2_4,
                'gender' => 'male',
                'address1' => 'Av. Siempre Viva 111',
                'address2' => 'Av. Siempre Viva 111',
                'city' => 'Lanus Este',
                'state' => 'Buenos Aires',
                'post_code' => '1824',
                'country' => 'AR',
                'language' => 'ESP',
                'active' => true,
                'email_verified' => true,
                'groups' => [
                    $super_admin_group
                ],
                'identifier' => '5',
            ],
            [
                'first_name' => 'Ian Y.',
                'last_name' => 'Choi',
                'email' => 'ianyrchoi@gmail.com',
                'password' => '1qaz2wsx',
                'password_enc' => \Auth\AuthHelper::AlgSHA1_V2_4,
                'gender' => 'male',
                'address1' => 'Av. Siempre Viva 111',
                'address2' => 'Av. Siempre Viva 111',
                'city' => 'Lanus Este',
                'state' => 'Buenos Aires',
                'post_code' => '1824',
                'country' => 'AR',
                'language' => 'ESP',
                'active' => true,
                'email_verified' => true,
                'groups' => [
                    $super_admin_group
                ],
                'identifier' => '6',
            ]
        ];

        foreach ($user_payloads as $payload){
            $user = UserFactory::build($payload);
            EntityManager::persist($user);
            $raw_password = $payload['password'];
            if(!$user->checkPassword($raw_password))
                throw new Exception("password verification failed !!!");
        }
        EntityManager::flush();
    }

    public function run()
    {

        DB::table('banned_ips')->delete();
        DB::table('user_exceptions_trail')->delete();
        DB::table('server_configuration')->delete();
        DB::table('server_extensions')->delete();

        DB::table('oauth2_client_api_scope')->delete();
        DB::table('oauth2_client_authorized_uri')->delete();
        DB::table('oauth2_access_token')->delete();
        DB::table('oauth2_refresh_token')->delete();
        DB::table('oauth2_asymmetric_keys')->delete();
        DB::table('oauth2_client')->delete();

        DB::table('openid_trusted_sites')->delete();
        DB::table('openid_associations')->delete();
        DB::table('user_actions')->delete();
        DB::table('user_groups')->delete();
        DB::table('users')->delete();
        DB::table('groups')->delete();

        DB::table('oauth2_api_endpoint_api_scope')->delete();
        DB::table('oauth2_api_endpoint')->delete();
        DB::table('oauth2_api_scope')->delete();
        DB::table('oauth2_api')->delete();
        DB::table('oauth2_resource_server')->delete();

        $this->createTestGroups();

        $this->createTestUsers();

        $this->seedServerConfiguration();
        $this->seedServerExtensions();
        $this->seedTestResourceServers();
        $this->seedApis();
        $this->seedBasicScopes();
        $this->seedResourceServerScopes();
        $this->seedApiScopes();
        $this->seedApiEndpointScopes();
        $this->seedApiScopeScopes();
        $this->seedUsersScopes();
        $this->seedUsersRegistrationScopes();
        $this->seedSSOScopes();
        $this->seedPublicCloudScopes();
        $this->seedPrivateCloudScopes();
        $this->seedConsultantScopes();

        //endpoints
        $this->seedResourceServerEndpoints();
        $this->seedApiEndpoints();
        $this->seedUsersEndpoints();
        $this->seedUserRegistrationEndpoints();
        $this->seedSSOEndpoints();
        /*
        $this->seedApiEndpointEndpoints();
        $this->seedScopeEndpoints();
        $this->seedPublicCloudsEndpoints();
        $this->seedPrivateCloudsEndpoints();
        $this->seedConsultantsEndpoints();
        */
        //clients
        $this->seedTestUsersAndClients();
    }

    private function seedServerConfiguration(){
        $config_payloads = [
            array(
                'key'   => 'Private.Association.Lifetime',
                'value' => '240',
            ),
            array(
                'key'   => 'Session.Association.Lifetime',
                'value' => '21600',
            ),
            array(
                'key'   => 'MaxFailed.Login.Attempts',
                'value' => '10',
            ),
            array(
                'key'   => 'MaxFailed.LoginAttempts.2ShowCaptcha',
                'value' => '3',
            ),
            array(
                'key'   => 'Nonce.Lifetime',
                'value' => '360',
            ),
            array(
                'key'   => 'Assets.Url',
                'value' => 'http://www.openstack.org/',
            ),
            //blacklist policy config values
             array(
                 'key'   => 'BannedIpLifeTimeSeconds',
                 'value' => '21600',
             ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MinutesWithoutExceptions',
                'value' => '5',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.ReplayAttackExceptionInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidNonceAttempts',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.InvalidNonceInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidOpenIdMessageExceptionAttempts',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.InvalidOpenIdMessageExceptionInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MaxOpenIdInvalidRealmExceptionAttempts',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.OpenIdInvalidRealmExceptionInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidOpenIdMessageModeAttempts',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.InvalidOpenIdMessageModeInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MaxInvalidOpenIdAuthenticationRequestModeAttempts',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.InvalidOpenIdAuthenticationRequestModeInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.MaxAuthenticationExceptionAttempts',
                'value' => '10',
            ),
            array(
                'key'   => 'BlacklistSecurityPolicy.AuthenticationExceptionInitialDelay',
                'value' => '20',
            ),
            array(
                'key'   => 'AuthorizationCodeRedeemPolicy.MinutesWithoutExceptions',
                'value' => '5',
            ),
            array(
                'key'   => 'AuthorizationCodeRedeemPolicy.MaxAuthCodeReplayAttackAttempts',
                'value' => '3',
            ),
            array(
                'key'   => 'AuthorizationCodeRedeemPolicy.AuthCodeReplayAttackInitialDelay',
                'value' => '10',
            ),
            array(
                'key'   => 'AuthorizationCodeRedeemPolicy.MaxInvalidAuthorizationCodeAttempts',
                'value' => '3',
            ),
            array(
                'key'   => 'AuthorizationCodeRedeemPolicy.InvalidAuthorizationCodeInitialDelay',
                'value' => '10',
            )
        ];
        foreach($config_payloads as $payload) {
            EntityManager::persist(ServerConfigurationFactory::build($payload));
        }
        EntityManager::flush();

    }

    private function seedServerExtensions(){

        $extensions_payloads = [
            array(
                'name'            => 'SREG_1_0',
                'namespace'       => 'http://openid.net/sreg/1.0',
                'active'          => true,
                'extension_class' => OpenIdSREGExtension_1_0::class,
                'description'     => 'OpenID Simple Registration 1.0 is an extension to the OpenID Authentication protocol that allows for very light-weight profile exchange.',
                'view_name'       => 'extensions.sreg',
            ),
            array(
                'name'            => 'AX',
                'namespace'       => 'http://openid.net/srv/ax/1.0',
                'active'          => false,
                'extension_class' => OpenIdAXExtension::class,
                'description'     => 'OpenID service extension for exchanging identity information between endpoints',
                'view_name'       =>'extensions.ax',
            ),
            array(
                'name'            => 'SREG',
                'namespace'       => 'http://openid.net/extensions/sreg/1.1',
                'active'          => true,
                'extension_class' => OpenIdSREGExtension::class,
                'description'     => 'OpenID Simple Registration is an extension to the OpenID Authentication protocol that allows for very light-weight profile exchange.',
                'view_name'       => 'extensions.sreg',
            ),
            array(
                'name'            => 'OAUTH2',
                'namespace'       => 'http://specs.openid.net/extensions/oauth/2.0',
                'active'          => true,
                'extension_class' => OpenIdOAuth2Extension::class,
                'description'     => 'The OpenID OAuth2 Extension describes how to make the OpenID Authentication and OAuth2 Core specifications work well together.',
                'view_name'       => 'extensions.oauth2',
            )
        ];
        foreach($extensions_payloads as $payload) {
            EntityManager::persist(ServerExtensionFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedTestResourceServers(){
        $current_realm          = Config::get('app.url');
        $components             = parse_url($current_realm);

        $resource_server_payloads = [
            array(
                'friendly_name'    => 'test resource server',
                'host'             => $components['host'],
                'ips'              => '127.0.0.1,10.0.0.0,2001:4800:7821:101:be76:4eff:fe06:858b,174.143.201.173',
                 'active'           => true
            ),
            array(
                'friendly_name'    => 'test resource server 2',
                'host'             => $components['host'],
                'ips'              => '10.0.0.0,2001:4800:7821:101:be76:4eff:fe06:858b,174.143.201.173',
                'active'           => true,
            )
        ];
        foreach($resource_server_payloads as $payload) {
            EntityManager::persist(ResourceServerFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedApis(){
        $resource_server_repository = EntityManager::getRepository(ResourceServer::class);

        $resource_server = $resource_server_repository->findOneBy([
            'friendly_name' => 'test resource server'
        ]);

        $api_payloads = [
            array(
                'name'               => 'resource-server',
                'active'             =>  true,
                'description'        => 'Resource Server CRUD operations',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'api',
                'active'          =>  true,
                'description'     => 'Api CRUD operations',
                'resource_server' => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'api-endpoint',
                'active'          =>  true,
                'description'     => 'Api Endpoints CRUD operations',
                'resource_server' => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'api-scope',
                'active'          =>  true,
                'description'     => 'Api Scopes CRUD operations',
                'resource_server' => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'users',
                'active'          =>  true,
                'description'     => 'User Info',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'user-registration',
                'active'          =>  true,
                'description'     => 'User Registration',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'sso',
                'active'          =>  true,
                'description'     => 'SSO Integration',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'public-clouds',
                'active'          =>  true,
                'description'     => 'Marketplace Public Clouds',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'private-clouds',
                'active'          =>  true,
                'description'     => 'Marketplace Private Clouds',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            ),
            array(
                'name'            => 'consultants',
                'active'          =>  true,
                'description'     => 'Marketplace Consultants',
                'resource_server'    => $resource_server,
                'logo'               => asset('/assets/img/apis/server.png')
            )
        ];

        foreach($api_payloads as $payload) {
            EntityManager::persist(ApiFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedBasicScopes(){
        //scopes

        $api_scope_payloads = [
            array(
                'name'               => OAuth2Protocol::OpenIdConnect_Scope,
                'short_description'  => 'OIDC',
                'description'        => 'OIDC',
                'system'             => true,
                'default'            => true,
                'active'             => true,
            ),
            array(
                'name'               => OAuth2Protocol::OfflineAccess_Scope,
                'short_description'  => 'allow to emit refresh tokens (offline access without user presence)',
                'description'        => 'allow to emit refresh tokens (offline access without user presence)',
                'system'             => true,
                'default'            => true,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedTestUsersAndClients(){

        $resource_server_repository = EntityManager::getRepository(ResourceServer::class);

        $resource_server = $resource_server_repository->findOneBy([
            'friendly_name' => 'test resource server'
        ]);

        $resource_server2 = $resource_server_repository->findOneBy([
            'friendly_name' => 'test resource server 2'
        ]);

        $user_repository = EntityManager::getRepository(User::class);

        $user = $user_repository->findOneBy(['email' => 'sebastian@tipit.net']);

        $trusted_site = OpenIdTrustedSiteFactory::build([
            'realm'  => 'https://www.test.com/',
            'policy' => IAuthService::AuthorizationResponse_AllowForever
        ]);

        $user->addTrustedSite($trusted_site);
        EntityManager::persist($user);
        //EntityManager::flush();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $client_payloads = [
            array(
                'app_name'             => 'oauth2_test_app',
                'app_description'      => 'oauth2_test_app',
                'app_logo'             => null,
                'client_id'            => '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client',
                'client_secret'        => 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg',
                'client_type'          => IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Web_App,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'owner'                  => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris' => 'https://www.test.com/oauth2,https://op.certification.openid.net:60393/authz_cb',
                'id_token_signed_response_alg'    => JSONWebSignatureAndEncryptionAlgorithms::HS512,
                'id_token_encrypted_response_alg' => JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
                'id_token_encrypted_response_enc' => JSONWebSignatureAndEncryptionAlgorithms::A256CBC_HS512,
                'client_secret_expires_at' => $now->add(new \DateInterval('P6M')),
            ),
            array
            (
                'app_name'             => 'oauth2_test_app2',
                'app_description'      => 'oauth2_test_app2',
                'app_logo'             => null,
                'client_id'            => 'Jiz87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x2.openstack.client',
                'client_secret'        => 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg',
                'client_type'          => IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Web_App,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretJwt,
                'token_endpoint_auth_signing_alg' => JSONWebSignatureAndEncryptionAlgorithms::HS512,
                'subject_type' => IClient::SubjectType_Pairwise,
                'owner'              => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris' => 'https://www.test.com/oauth2',
                'id_token_signed_response_alg'    => JSONWebSignatureAndEncryptionAlgorithms::HS512,
                'id_token_encrypted_response_alg' => JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
                'id_token_encrypted_response_enc' => JSONWebSignatureAndEncryptionAlgorithms::A256CBC_HS512,
                'client_secret_expires_at'        => $now->add(new \DateInterval('P6M')),
            ),
            array(
                'app_name'             => 'oauth2_test_app3',
                'app_description'      => 'oauth2_test_app3',
                'app_logo'             => null,
                'client_id'            => 'Jiz87D8/Vcvr6fvQbH4HyNgwTlfSyQ33.openstack.client',
                'client_secret'        => 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N585OtGKhg55',
                'client_type'          => IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Web_App,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'owner'              => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris' => 'https://www.test.com/oauth2',
                'id_token_signed_response_alg'    => JSONWebSignatureAndEncryptionAlgorithms::HS512,
                'userinfo_signed_response_alg'    => JSONWebSignatureAndEncryptionAlgorithms::RS512,
                'client_secret_expires_at' => $now->add(new \DateInterval('P6M')),
            ),
            array(
                'app_name'             => 'oauth2.service',
                'app_description'      => 'oauth2.service',
                'app_logo'             => null,
                'client_id'            => '11z87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client',
                'client_secret'        => '11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg',
                'client_type'          => IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Service,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'owner'              => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris' => 'https://www.test.com/oauth2',
                'client_secret_expires_at' => $now->add(new \DateInterval('P6M')),
            ),
            array(
                'app_name'             => 'oauth2_test_app_public',
                'app_description'      => 'oauth2_test_app_public',
                'app_logo'             => null,
                'client_id'            => '1234/Vcvr6fvQbH4HyNgwKlfSyQ3x.openstack.client',
                'client_secret'        => null,
                'application_type'     => IClient::ApplicationType_JS_Client,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_PrivateKeyJwt,
                'token_endpoint_auth_signing_alg' => JSONWebSignatureAndEncryptionAlgorithms::RS512,
                'owner'              => $user,
                'rotate_refresh_token' => false,
                'use_refresh_token'    => false,
                'redirect_uris' => 'https://www.test.com/oauth2',
            ),
            array(
                'app_name'             => 'oauth2_test_app_public_pkce',
                'app_description'      => 'oauth2_test_app_public_pkce',
                'app_logo'             => null,
                'client_id'            => '1234/Vcvr6fvQbH4HyNgwKlfSpkce.openstack.client',
                'client_secret'        => null,
                'application_type'     => IClient::ApplicationType_JS_Client,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_None,
                'owner'              => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris' => 'https://www.test.com/oauth2',
                'pkce_enabled' => true,
            ),
            array(
                'app_name'             => 'oauth2_native_app',
                'app_description'      => 'oauth2_native_app',
                'app_logo'             => null,
                'client_id'            => 'Jiz87D8/Vcvr6fvQbH4HyNgwKlfSyQ3x.android.openstack.client',
                'client_secret'        => '11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhgfdfdfdf',
                'client_type'          => IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Native,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'owner'              => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris'        => 'androipapp://oidc_endpoint_callback',
            ),
            array(
                'app_name'             => 'oauth2_native_app2',
                'app_description'      => 'oauth2_native_app2',
                'app_logo'             => null,
                'client_id'            => 'Jiz87D8/Vcvr6fvQbH4HyNgwKlfSyQ3x.android2.openstack.client',
                'client_secret'        => '11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhg11c/6Y5N7kOtGKhgfdfdfdf',
                'client_type'          => IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Native,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_PrivateKeyJwt,
                'token_endpoint_auth_signing_alg' => JSONWebSignatureAndEncryptionAlgorithms::RS512,
                'owner'              => $user,
                'rotate_refresh_token' => true,
                'use_refresh_token'    => true,
                'redirect_uris'        => 'androipapp://oidc_endpoint_callback2',
            ),
            array(
                'app_name'             => 'oauth2_test_app_public_2',
                'app_description'      => 'oauth2_test_app_public_2',
                'app_logo'             => null,
                'client_id'            => 'Jiz87D8/Vcvr6fvQbH4HyNgwKlfSyQ2x.openstack.client',
                'client_secret'        => null,
                'client_type'          => IClient::ClientType_Public,
                'application_type'     => IClient::ApplicationType_JS_Client,
                'owner'                => $user,
                'rotate_refresh_token' => false,
                'use_refresh_token'    => false,
                'redirect_uris' => 'https://www.test.com/oauth2'
            ),
            array(
                'app_name'             => 'resource_server_client',
                'app_description'      => 'resource_server_client',
                'app_logo'             => null,
                'client_id'            => 'resource.server.1.openstack.client',
                'client_secret'        => '123456789123456789123456789123456789123456789',
                'client_type'          =>  IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Service,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'resource_server'   => $resource_server,
                'rotate_refresh_token' => false,
                'use_refresh_token'    => false,
                'client_secret_expires_at' => $now->add(new \DateInterval('P6M')),
            ),
            array(
                'app_name'             => 'resource_server_client2',
                'app_description'      => 'resource_server_client2',
                'app_logo'             => null,
                'client_id'            => 'resource.server.2.openstack.client',
                'client_secret'        => '123456789123456789123456789123456789123456789',
                'client_type'          =>  IClient::ClientType_Confidential,
                'application_type'     => IClient::ApplicationType_Service,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'resource_server'       => $resource_server2,
                'rotate_refresh_token' => false,
                'use_refresh_token'    => false,
                'client_secret_expires_at' => $now->add(new \DateInterval('P6M')),
            )
        ];

        foreach ($client_payloads as $payload){
            EntityManager::persist(ClientFactory::build($payload));
        }

        EntityManager::flush();

        $client_repository = EntityManager::getRepository(Client::class);

        $client_confidential   = $client_repository->findOneBy(['app_name' => 'oauth2_test_app']);
        $client_confidential2  = $client_repository->findOneBy(['app_name' => 'oauth2_test_app2']);
        $client_confidential3  = $client_repository->findOneBy(['app_name' => 'oauth2_test_app3']);
        $client_public         = $client_repository->findOneBy(['app_name' => 'oauth2_test_app_public']);
        $client_public2         = $client_repository->findOneBy(['app_name' => 'oauth2_test_app_public_pkce']);
        $client_service        = $client_repository->findOneBy(['app_name' => 'oauth2.service']);
        $client_native         = $client_repository->findOneBy(['app_name' => 'oauth2_native_app']);
        $client_native2        = $client_repository->findOneBy(['app_name' => 'oauth2_native_app2']);

        //attach all scopes
        $scopes_repository = EntityManager::getRepository(ApiScope::class);
        $scopes = $scopes_repository->findAll();

        foreach($scopes as $scope)
        {
            $client_confidential->addScope($scope);
            $client_confidential2->addScope($scope);
            $client_confidential3->addScope($scope);
            $client_public->addScope($scope);
            $client_public2->addScope($scope);
            $client_service->addScope($scope);
            $client_native->addScope($scope);
            $client_native2->addScope($scope);
        }

        $now =  new \DateTime('now', new DateTimeZone('UTC'));
        $to   = new \DateTime('now', new DateTimeZone('UTC'));
        $to->add(new \DateInterval('P31D'));

        $public_key_1 = ClientPublicKey::buildFromPEM(
            'public_key_1',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Encryption,
            self::$client_public_key_1,
            JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
            true,
            $now,
            $to
        );

        $client_confidential->addPublicKey($public_key_1);

        $public_key_2 = ClientPublicKey::buildFromPEM(
            'public_key_2',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Signature,
            self::$client_public_key_2,
            JSONWebSignatureAndEncryptionAlgorithms::RS512,
            true,
            $now,
            $to
        );

        $client_confidential->addPublicKey($public_key_2);

        // confidential client 2
        $public_key_11 = ClientPublicKey::buildFromPEM(
            'public_key_1',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Encryption,
            self::$client_public_key_1,
            JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
            true,
            $now,
            $to
        );

        $client_confidential2->addPublicKey($public_key_11);

        $public_key_22 = ClientPublicKey::buildFromPEM(
            'public_key_2',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Signature,
            self::$client_public_key_2,
            JSONWebSignatureAndEncryptionAlgorithms::RS512,
            true,
            $now,
            $to
        );

        $client_confidential2->addPublicKey($public_key_22);

        // public native client
        $public_key_33 = ClientPublicKey::buildFromPEM(
            'public_key_33',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Encryption,
            self::$client_public_key_1,
            JSONWebSignatureAndEncryptionAlgorithms::RSA_OAEP_256,
            true,
            $now,
            $to
        );

        $client_native2->addPublicKey($public_key_33);

        $public_key_44 = ClientPublicKey::buildFromPEM(
            'public_key_44',
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Signature,
            self::$client_public_key_2,
            JSONWebSignatureAndEncryptionAlgorithms::RS512,
            true,
            $now,
            $to
        );

        $client_native2->addPublicKey($public_key_44);

        // server private keys

        $pkey_1 = ServerPrivateKey::build
        (
            'server_key_enc',
            $now,
            $to,
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Encryption,
            JSONWebSignatureAndEncryptionAlgorithms::RSA1_5,
            true,
            TestKeys::$private_key_pem
        );

        EntityManager::persist($pkey_1);

        $pkey_2 = ServerPrivateKey::build
        (
            'server_key_sig',
            $now,
            $to,
            JSONWebKeyTypes::RSA,
            JSONWebKeyPublicKeyUseValues::Signature,
            JSONWebSignatureAndEncryptionAlgorithms::RS512,
            true,
            TestKeys::$private_key_pem
        );

        EntityManager::persist($pkey_2);

        EntityManager::flush();
    }

    private function seedResourceServerScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $resource_server = $api_repository->findOneBy(['name' => 'resource-server']);

        $current_realm = Config::get('app.url');

        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/resource-server/read',$current_realm),
                'short_description'  => 'Resource Server Read Access',
                'description'        => 'Resource Server Read Access',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/resource-server/read.page',$current_realm),
                'short_description'  => 'Resource Server Page Read Access',
                'description'        => 'Resource Server Page Read Access',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/resource-server/write',$current_realm),
                'short_description'  => 'Resource Server Write Access',
                'description'        => 'Resource Server Write Access',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/resource-server/delete',$current_realm),
                'short_description'  => 'Resource Server Delete Access',
                'description'        => 'Resource Server Delete Access',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/resource-server/update',$current_realm),
                'short_description'  => 'Resource Server Update Access',
                'description'        => 'Resource Server Update Access',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/resource-server/update.status',$current_realm),
                'short_description'  => 'Resource Server Update Status',
                'description'        => 'Resource Server Update Status',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/resource-server/regenerate.secret',$current_realm),
                'short_description'  => 'Resource Server Regenerate Client Secret',
                'description'        => 'Resource Server Regenerate Client Secret',
                'api'                => $resource_server,
                'system'             => true,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedApiScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'api']);

        $current_realm = Config::get('app.url');

        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/api/read',$current_realm),
                'short_description'  => 'Get Api',
                'description'        => 'Get Api',
                'api'                => $api,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api/delete',$current_realm),
                'short_description'  => 'Deletes Api',
                'description'        => 'Deletes Api',
                'api'                => $api,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api/write',$current_realm),
                'short_description'  => 'Create Api',
                'description'        => 'Create Api',
                'api'                => $api,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api/update',$current_realm),
                'short_description'  => 'Update Api',
                'description'        => 'Update Api',
                'api'                => $api,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api/update.status',$current_realm),
                'short_description'  => 'Update Api Status',
                'description'        => 'Update Api Status',
                'api'                => $api,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api/read.page',$current_realm),
                'short_description'  => 'Get Api By Page',
                'description'        => 'Get Api By Page',
                'api'                => $api,
                'system'             => true,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedApiEndpointScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api_endpoint = $api_repository->findOneBy(['name' => 'api-endpoint']);
        $current_realm = Config::get('app.url');
        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/api-endpoint/read',$current_realm),
                'short_description'  => 'Get Api Endpoint',
                'description'        => 'Get Api Endpoint',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/delete',$current_realm),
                'short_description'  => 'Deletes Api Endpoint',
                'description'        => 'Deletes Api Endpoint',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/write',$current_realm),
                'short_description'  => 'Create Api Endpoint',
                'description'        => 'Create Api Endpoint',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/update',$current_realm),
                'short_description'  => 'Update Api Endpoint',
                'description'        => 'Update Api Endpoint',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/update.status',$current_realm),
                'short_description'  => 'Update Api Endpoint Status',
                'description'        => 'Update Api Endpoint Status',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/read.page',$current_realm),
                'short_description'  => 'Get Api Endpoints By Page',
                'description'        => 'Get Api Endpoints By Page',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/add.scope',$current_realm),
                'short_description'  => 'Add required scope to endpoint',
                'description'        => 'Add required scope to endpoint',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-endpoint/remove.scope',$current_realm),
                'short_description'  => 'Remove required scope to endpoint',
                'description'        => 'Remove required scope to endpoint',
                'api'                => $api_endpoint,
                'system'             => true,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();

    }

    private function seedApiScopeScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api_scope      = $api_repository->findOneBy(['name' => 'api-scope']);
        $current_realm  = Config::get('app.url');

        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/api-scope/read',$current_realm),
                'short_description'  => 'Get Api Scope',
                'description'        => 'Get Api Scope',
                'api'                => $api_scope,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-scope/delete',$current_realm),
                'short_description'  => 'Deletes Api Scope',
                'description'        => 'Deletes Api Scope',
                'api'                => $api_scope,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-scope/write',$current_realm),
                'short_description'  => 'Create Api Scope',
                'description'        => 'Create Api Scope',
                'api'                => $api_scope,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-scope/update',$current_realm),
                'short_description'  => 'Update Api Scope',
                'description'        => 'Update Api Scope',
                'api'                => $api_scope,
                'system'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-scope/update.status',$current_realm),
                'short_description'  => 'Update Api Scope Status',
                'description'        => 'Update Api Scope Status',
                'api'                => $api_scope,
                'system'             => true,
                'active'             => true,
            ),
            array(
                'name'               => sprintf('%s/api-scope/read.page',$current_realm),
                'short_description'  => 'Get Api Scopes By Page',
                'description'        => 'Get Api Scopes By Page',
                'api'                => $api_scope,
                'system'             => true,
                'active'             => true,
            )
        ];


        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();

    }

    private function seedUsersScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'users']);

        $current_realm = Config::get('app.url');


        $api_scope_payloads = [
            array(
                'name'               => 'profile',
                'short_description'  => 'This scope value requests access to the End-Users default profile Claims',
                'description'        => 'This scope value requests access to the End-Users default profile Claims, which are: name, family_name, given_name, middle_name, nickname, preferred_username, profile, picture, website, gender, birthdate, zoneinfo, locale, and updated_at',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            ),
            array(
                'name'               => 'email',
                'short_description'  => 'This scope value requests access to the email and email_verified Claims',
                'description'        => 'This scope value requests access to the email and email_verified Claims',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            ),
            [
                'name'               => 'address',
                'short_description'  => 'This scope value requests access to the address Claim.',
                'description'        => 'This scope value requests access to the address Claim.',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            ],
            [
                'name'               => IUserScopes::MeRead,
                'short_description'  => 'Allows access to read your Profile',
                'description'        => 'Allows access to read your Profile',
                'api'                => $api,
                'system'             => false,
                'default'            => false,
                'active'             => true,
            ],
            [
                'name'               => IUserScopes::MeWrite,
                'short_description'  => 'Allows access to write your Profile',
                'description'        => 'Allows access to write your Profile',
                'api'                => $api,
                'system'             => false,
                'default'            => false,
                'active'             => true,
            ]
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedUsersRegistrationScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'user-registration']);

        $current_realm = Config::get('app.url');


        $api_scope_payloads = [
            array(
                'name'               => IUserScopes::Registration,
                'short_description'  => 'request-user-registration',
                'description'        => 'request-user-registration',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            ),
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedSSOScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'sso']);

        $current_realm = Config::get('app.url');


        $api_scope_payloads = [
            array(
                'name'               => IUserScopes::SSO,
                'short_description'  => 'Allows SSO integration',
                'description'        => 'Allows SSO integration',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            ),
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedPublicCloudScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'public-clouds']);

        $current_realm = Config::get('app.url');


        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/public-clouds/read',$current_realm),
                'short_description'  => 'Get Public Clouds',
                'description'        => 'Get Public Clouds',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedPrivateCloudScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'private-clouds']);

        $current_realm = Config::get('app.url');


        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/private-clouds/read',$current_realm),
                'short_description'  => 'Get Private Clouds',
                'description'        => 'Get Private Clouds',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }


    private function seedConsultantScopes(){

        $api_repository = EntityManager::getRepository(Api::class);
        $api = $api_repository->findOneBy(['name' => 'consultants']);

        $current_realm = Config::get('app.url');

        $api_scope_payloads = [
            array(
                'name'               => sprintf('%s/consultants/read',$current_realm),
                'short_description'  => 'Get Consultants',
                'description'        => 'Get Consultants',
                'api'                => $api,
                'system'             => false,
                'active'             => true,
            )
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiScopeFactory::build($payload));
        }
        EntityManager::flush();
    }

    private function seedResourceServerEndpoints(){

        $current_realm  = Config::get('app.url');

        SeedUtils::seedApiEndpoints('resource-server', [
            array(
                'name'            => 'create-resource-server',
                'route'           => '/api/v1/resource-servers',
                'http_method'     => 'POST',
                'scopes'      => [
                    sprintf('%s/resource-server/write', $current_realm)
                ],
            ),
            array(
                'name'            => 'get-resource-server',
                'route'           => '/api/v1/resource-servers/{id}',
                'http_method'     => 'GET',
                'scopes' => [
                    sprintf('%s/resource-server/read',$current_realm)
                ]
            ),
            array(
                'name'            => 'resource-server-regenerate-secret',
                'route'           => '/api/v1/resource-servers/{id}/client-secret',
                'http_method'     => 'PUT',
                'scopes' => [
                    sprintf('%s/resource-server/write',$current_realm),
                    sprintf('%s/resource-server/regenerate.secret',$current_realm)
                ]
            ),
            array(
                'name'            => 'resource-server-get-page',
                'route'           => '/api/v1/resource-servers',
                'http_method'     => 'GET',
                'scopes' => [
                    sprintf('%s/resource-server/read',$current_realm),
                    sprintf('%s/resource-server/read.page',$current_realm)
                ]
            ),
            array(
                'name'            => 'resource-server-delete',
                'route'           => '/api/v1/resource-servers/{id}',
                'http_method'     => 'DELETE',
                'scopes' => [
                    sprintf('%s/resource-server/delete',$current_realm)
                ]
            ),
            array(
                'name'            => 'resource-server-update',
                'route'           => '/api/v1/resource-servers',
                'http_method'     => 'PUT',
                'scopes' => [
                    sprintf('%s/resource-server/write',$current_realm)
                ]
            ),
            array(
                'name'            => 'resource-server-update-status',
                'route'           => '/api/v1/resource-servers/{id}/status/{active}',
                'http_method'     => 'PUT',
                'scopes' => [
                    sprintf('%s/resource-server/write',$current_realm)
                ]
            )
        ]);

    }

    private function seedApiEndpoints(){

        $current_realm  = Config::get('app.url');

        SeedUtils::seedApiEndpoints('api', [
            array(
                'name'            => 'get-api',
                'route'           => '/api/v1/api/{id}',
                'http_method'     => 'GET',
                'scopes' => [
                    sprintf('%s/api/read', $current_realm)
                ]
            ),
            array(
                'name'            => 'delete-api',
                'route'           => '/api/v1/api/{id}',
                'http_method'     => 'DELETE',
                'scopes' => [
                    sprintf('%s/api/delete',$current_realm)
                ]
            ),
            array(
                'name'            => 'create-api',
                'route'           => '/api/v1/api',
                'http_method'     => 'POST',
                'scopes' => [
                    sprintf('%s/api/write',$current_realm)
                ]
            ),
            array(
                'name'            => 'update-api',
                'route'           => '/api/v1/api',
                'http_method'     => 'PUT',
                'scopes' => [
                    sprintf('%s/api/update',$current_realm)
                ]
            ),
            array(
                'name'            => 'update-api-status',
                'route'           => '/api/v1/api/status/{id}/{active}',
                'http_method'     => 'GET',
                'scopes' => [
                    sprintf('%s/api/update.status',$current_realm)
                ]
            ),
            array(
                'name'            => 'api-get-page',
                'route'           => '/api/v1/api/{page_nbr}/{page_size}',
                'http_method'     => 'GET',
                'scopes' => [
                    sprintf('%s/api/read', $current_realm),
                    sprintf('%s/api/read.page',$current_realm)
                ]
            )
        ]);

    }

    private function seedUsersEndpoints(){
        $api_repository = EntityManager::getRepository(Api::class);
        $endpoint_repository = EntityManager::getRepository(ApiEndpoint::class);
        $users = $api_repository->findOneBy(['name' => 'users']);

        $api_scope_payloads = [
           [
                'name'            => 'get-user-info',
                'active'          =>  true,
                'api'          => $users,
                'route'           => '/api/v1/users/me',
                'http_method'     => 'GET'
            ],
            [
                'name'            => 'get-user-claims-get',
                'active'          =>  true,
                'api'             => $users,
                'route'           => '/api/v1/users/info',
                'http_method'     => 'GET'
            ],
            [
                'name'            => 'get-user-claims-post',
                'active'          =>  true,
                'api'             => $users,
                'route'           => '/api/v1/users/info',
                'http_method'     => 'POST'
            ],
            [
                'name' => 'update-my-user',
                'active' => true,
                'route' => '/api/v1/users/me',
                'api'             => $users,
                'http_method' => 'PUT',
            ],
            [
                'name' => 'update-my-user-pic',
                'active' => true,
                'route' => '/api/v1/users/me/pic',
                'api'             => $users,
                'http_method' => 'PUT',
            ],
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiEndpointFactory::build($payload));
        }
        EntityManager::flush();

        $api_scope_repository = EntityManager::getRepository(ApiScope::class);
        $profile_scope = $api_scope_repository->findOneBy(['name' => 'profile']);
        $email_scope   = $api_scope_repository->findOneBy(['name' => 'email']);
        $address_scope = $api_scope_repository->findOneBy(['name' => 'address']);
        $me_write = $api_scope_repository->findOneBy(['name' => IUserScopes::MeWrite]);

        foreach($api_scope_payloads as $payload) {
            $endpoint = $endpoint_repository->findOneBy(['name' => $payload['name']]);
            $endpoint->addScope($address_scope);
            $endpoint->addScope($email_scope);
            $endpoint->addScope($profile_scope);
            $endpoint->addScope($me_write);
            EntityManager::persist($endpoint);
        }

        EntityManager::flush();
    }

    private function seedUserRegistrationEndpoints(){
        $api_repository = EntityManager::getRepository(Api::class);
        $endpoint_repository = EntityManager::getRepository(ApiEndpoint::class);
        $api = $api_repository->findOneBy(['name' => 'user-registration']);

        $api_scope_payloads = [
            array(
                'name'            => 'request-user-registration',
                'active'          =>  true,
                'api'             => $api,
                'route'           => '/api/v1/user-registration-requests',
                'http_method'     => 'POST'
            ),
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiEndpointFactory::build($payload));
        }
        EntityManager::flush();

        $api_scope_repository = EntityManager::getRepository(ApiScope::class);
        $scope = $api_scope_repository->findOneBy(['name' => IUserScopes::Registration]);

        foreach($api_scope_payloads as $payload) {
            $endpoint = $endpoint_repository->findOneBy(['name' => $payload['name']]);
            $endpoint->addScope($scope);
            EntityManager::persist($endpoint);
        }

        EntityManager::flush();
    }

    private function seedSSOEndpoints(){
        $api_repository = EntityManager::getRepository(Api::class);
        $endpoint_repository = EntityManager::getRepository(ApiEndpoint::class);
        $api = $api_repository->findOneBy(['name' => 'sso']);

        $api_scope_payloads = [
            [
                'name' => 'sso-disqus',
                'active'          =>  true,
                'api'             => $api,
                'route' => '/api/v1/sso/disqus/{forum_slug}/profile',
                'http_method'     => 'GET'
            ],
            [
                'name' => 'sso-rocket-chat',
                'active'          =>  true,
                'api'             => $api,
                'route' => '/api/v1/sso/rocket-chat/{forum_slug}/profile',
                'http_method'     => 'GET'
            ],
            [
                'name' => 'sso-stream-chat',
                'active' => true,
                'api'             => $api,
                'route' => '/api/v1/sso/stream-chat/{forum_slug}/profile',
                'http_method' => 'GET',

            ],
        ];

        foreach($api_scope_payloads as $payload) {
            EntityManager::persist(ApiEndpointFactory::build($payload));
        }
        EntityManager::flush();

        $api_scope_repository = EntityManager::getRepository(ApiScope::class);
        $scope = $api_scope_repository->findOneBy(['name' => \App\libs\OAuth2\IUserScopes::SSO]);

        foreach($api_scope_payloads as $payload) {
            $endpoint = $endpoint_repository->findOneBy(['name' => $payload['name']]);
            $endpoint->addScope($scope);
            EntityManager::persist($endpoint);
        }

        EntityManager::flush();
    }


    /*
    private function seedApiEndpointEndpoints(){

        $current_realm  = Config::get('app.url');
        $api_api_endpoint           = Api::where('name','=','api-endpoint')->first();

        ApiEndpoint::create(
            array(
                'name'            => 'get-api-endpoint',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint/{id}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'delete-api-endpoint',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint/{id}',
                'http_method'     => 'DELETE'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'create-api-endpoint',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint',
                'http_method'     => 'POST'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'update-api-endpoint',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint',
                'http_method'     => 'PUT'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'update-api-endpoint-status',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint/status/{id}/{active}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'api-endpoint-get-page',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint/{page_nbr}/{page_size}',
                'http_method'     => 'GET'
            )
        );


        ApiEndpoint::create(
            array(
                'name'            => 'add-api-endpoint-scope',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint/scope/add/{id}/{scope_id}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'remove-api-endpoint-scope',
                'active'          =>  true,
                'api_id'          => $api_api_endpoint->id,
                'route'           => '/api/v1/api-endpoint/scope/remove/{id}/{scope_id}',
                'http_method'     => 'GET'
            )
        );

        //endpoint api endpoint scopes

        $api_endpoint_read_scope               = ApiScope::where('name','=',sprintf('%s/api-endpoint/read',$current_realm))->first();
        $api_endpoint_write_scope              = ApiScope::where('name','=',sprintf('%s/api-endpoint/write',$current_realm))->first();
        $api_endpoint_read_page_scope          = ApiScope::where('name','=',sprintf('%s/api-endpoint/read.page',$current_realm))->first();
        $api_endpoint_delete_scope             = ApiScope::where('name','=',sprintf('%s/api-endpoint/delete',$current_realm))->first();
        $api_endpoint_update_scope             = ApiScope::where('name','=',sprintf('%s/api-endpoint/update',$current_realm))->first();
        $api_endpoint_update_status_scope      = ApiScope::where('name','=',sprintf('%s/api-endpoint/update.status',$current_realm))->first();
        $api_endpoint_add_scope_scope          = ApiScope::where('name','=',sprintf('%s/api-endpoint/add.scope',$current_realm))->first();
        $api_endpoint_remove_scope_scope       = ApiScope::where('name','=',sprintf('%s/api-endpoint/remove.scope',$current_realm))->first();

        $endpoint_api_endpoint_get                  = ApiEndpoint::where('name','=','get-api-endpoint')->first();
        $endpoint_api_endpoint_get->scopes()->attach($api_endpoint_read_scope->id);

        $endpoint_api_endpoint_get_page             = ApiEndpoint::where('name','=','api-endpoint-get-page')->first();
        $endpoint_api_endpoint_get_page->scopes()->attach($api_endpoint_read_scope->id);
        $endpoint_api_endpoint_get_page->scopes()->attach($api_endpoint_read_page_scope->id);

        $endpoint_api_endpoint_delete               = ApiEndpoint::where('name','=','delete-api-endpoint')->first();
        $endpoint_api_endpoint_delete->scopes()->attach($api_endpoint_delete_scope->id);

        $endpoint_api_endpoint_create               = ApiEndpoint::where('name','=','create-api-endpoint')->first();
        $endpoint_api_endpoint_create->scopes()->attach($api_endpoint_write_scope->id);

        $endpoint_api_endpoint_update       = ApiEndpoint::where('name','=','update-api-endpoint')->first();
        $endpoint_api_endpoint_update->scopes()->attach($api_endpoint_update_scope->id);

        $endpoint_api_add_api_endpoint_scope        = ApiEndpoint::where('name','=','add-api-endpoint-scope')->first();
        $endpoint_api_add_api_endpoint_scope->scopes()->attach($api_endpoint_write_scope->id);
        $endpoint_api_add_api_endpoint_scope->scopes()->attach($api_endpoint_add_scope_scope->id);

        $endpoint_api_remove_api_endpoint_scope        = ApiEndpoint::where('name','=','remove-api-endpoint-scope')->first();
        $endpoint_api_remove_api_endpoint_scope->scopes()->attach($api_endpoint_write_scope->id);
        $endpoint_api_remove_api_endpoint_scope->scopes()->attach($api_endpoint_remove_scope_scope->id);


        $endpoint_api_endpoint_update_status        = ApiEndpoint::where('name','=','update-api-endpoint-status')->first();
        $endpoint_api_endpoint_update_status->scopes()->attach($api_endpoint_update_scope->id);
        $endpoint_api_endpoint_update_status->scopes()->attach($api_endpoint_update_status_scope->id);

    }

    private function seedScopeEndpoints(){
        $api_scope                  = Api::where('name','=','api-scope')->first();
        $current_realm  = Config::get('app.url');
        // endpoints scopes

        ApiEndpoint::create(
            array(
                'name'            => 'get-scope',
                'active'          =>  true,
                'api_id'          => $api_scope->id,
                'route'           => '/api/v1/api-scope/{id}',
                'http_method'     => 'GET'
            )
        );


        ApiEndpoint::create(
            array(
                'name'            => 'delete-scope',
                'active'          =>  true,
                'api_id'          => $api_scope->id,
                'route'           => '/api/v1/api-scope/{id}',
                'http_method'     => 'DELETE'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'create-scope',
                'active'          =>  true,
                'api_id'          => $api_scope->id,
                'route'           => '/api/v1/api-scope',
                'http_method'     => 'POST'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'update-scope',
                'active'          =>  true,
                'api_id'          => $api_scope->id,
                'route'           => '/api/v1/api-scope',
                'http_method'     => 'PUT'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'update-scope-status',
                'active'          =>  true,
                'api_id'          => $api_scope->id,
                'route'           => '/api/v1/api-scope/status/{id}/{active}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'scope-get-page',
                'active'          =>  true,
                'api_id'          => $api_scope->id,
                'route'           => '/api/v1/api-scope/{page_nbr}/{page_size}',
                'http_method'     => 'GET'
            )
        );

        $api_scope_read_scope               = ApiScope::where('name','=',sprintf('%s/api-scope/read',$current_realm))->first();
        $api_scope_write_scope              = ApiScope::where('name','=',sprintf('%s/api-scope/write',$current_realm))->first();
        $api_scope_read_page_scope          = ApiScope::where('name','=',sprintf('%s/api-scope/read.page',$current_realm))->first();
        $api_scope_delete_scope             = ApiScope::where('name','=',sprintf('%s/api-scope/delete',$current_realm))->first();
        $api_scope_update_scope             = ApiScope::where('name','=',sprintf('%s/api-scope/update',$current_realm))->first();
        $api_scope_update_status_scope      = ApiScope::where('name','=',sprintf('%s/api-scope/update.status',$current_realm))->first();


        $endpoint_api_scope_get             = ApiEndpoint::where('name','=','get-scope')->first();
        $endpoint_api_scope_get->scopes()->attach($api_scope_read_scope->id);

        $endpoint_api_scope_get_page        = ApiEndpoint::where('name','=','scope-get-page')->first();
        $endpoint_api_scope_get_page->scopes()->attach($api_scope_read_scope->id);
        $endpoint_api_scope_get_page->scopes()->attach($api_scope_read_page_scope->id);

        $endpoint_api_scope_delete          = ApiEndpoint::where('name','=','delete-scope')->first();
        $endpoint_api_scope_delete->scopes()->attach($api_scope_delete_scope->id);

        $endpoint_api_scope_create          = ApiEndpoint::where('name','=','create-scope')->first();
        $endpoint_api_scope_create->scopes()->attach($api_scope_write_scope->id);

        $endpoint_api_scope_update               = ApiEndpoint::where('name','=','update-scope')->first();
        $endpoint_api_scope_update->scopes()->attach($api_scope_update_scope->id);

        $endpoint_api_scope_update_status        = ApiEndpoint::where('name','=','update-scope-status')->first();
        $endpoint_api_scope_update_status->scopes()->attach($api_scope_update_scope->id);
        $endpoint_api_scope_update_status->scopes()->attach($api_scope_update_status_scope->id);
    }

    private function seedPublicCloudsEndpoints(){
        $public_clouds  = Api::where('name','=','public-clouds')->first();
        $current_realm  = Config::get('app.url');
        // endpoints scopes

        ApiEndpoint::create(
            array(
                'name'            => 'get-public-clouds',
                'active'          =>  true,
                'api_id'          => $public_clouds->id,
                'route'           => '/api/v1/marketplace/public-clouds',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'get-public-cloud',
                'active'          =>  true,
                'api_id'          => $public_clouds->id,
                'route'           => '/api/v1/marketplace/public-clouds/{id}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'get-public-cloud-datacenters',
                'active'          =>  true,
                'api_id'          => $public_clouds->id,
                'route'           => '/api/v1/marketplace/public-clouds/{id}/data-centers',
                'http_method'     => 'GET'
            )
        );

        $public_cloud_read_scope           = ApiScope::where('name','=',sprintf('%s/public-clouds/read',$current_realm))->first();

        $endpoint_get_public_clouds            = ApiEndpoint::where('name','=','get-public-clouds')->first();
        $endpoint_get_public_clouds->scopes()->attach($public_cloud_read_scope->id);

        $endpoint_get_public_cloud        = ApiEndpoint::where('name','=','get-public-cloud')->first();
        $endpoint_get_public_cloud->scopes()->attach($public_cloud_read_scope->id);

        $endpoint_get_public_cloud_datacenters = ApiEndpoint::where('name','=','get-public-cloud-datacenters')->first();
        $endpoint_get_public_cloud_datacenters->scopes()->attach($public_cloud_read_scope->id);
    }

    private function seedPrivateCloudsEndpoints(){
        $private_clouds  = Api::where('name','=','private-clouds')->first();
        $current_realm  = Config::get('app.url');
        // endpoints scopes

        ApiEndpoint::create(
            array(
                'name'            => 'get-private-clouds',
                'active'          =>  true,
                'api_id'          => $private_clouds->id,
                'route'           => '/api/v1/marketplace/private-clouds',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'get-private-cloud',
                'active'          =>  true,
                'api_id'          => $private_clouds->id,
                'route'           => '/api/v1/marketplace/private-clouds/{id}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'get-private-cloud-datacenters',
                'active'          =>  true,
                'api_id'          => $private_clouds->id,
                'route'           => '/api/v1/marketplace/private-clouds/{id}/data-centers',
                'http_method'     => 'GET'
            )
        );

        $private_cloud_read_scope           = ApiScope::where('name','=',sprintf('%s/private-clouds/read',$current_realm))->first();

        $endpoint_get_private_clouds            = ApiEndpoint::where('name','=','get-private-clouds')->first();
        $endpoint_get_private_clouds->scopes()->attach($private_cloud_read_scope->id);

        $endpoint_get_private_cloud        = ApiEndpoint::where('name','=','get-private-cloud')->first();
        $endpoint_get_private_cloud->scopes()->attach($private_cloud_read_scope->id);

        $endpoint_get_private_cloud_datacenters = ApiEndpoint::where('name','=','get-private-cloud-datacenters')->first();
        $endpoint_get_private_cloud_datacenters->scopes()->attach($private_cloud_read_scope->id);

    }

    private function seedConsultantsEndpoints(){

        $consultants  = Api::where('name','=','consultants')->first();
        $current_realm  = Config::get('app.url');
        // endpoints scopes

        ApiEndpoint::create(
            array(
                'name'            => 'get-consultants',
                'active'          =>  true,
                'api_id'          => $consultants->id,
                'route'           => '/api/v1/marketplace/consultants',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'get-consultant',
                'active'          =>  true,
                'api_id'          => $consultants->id,
                'route'           => '/api/v1/marketplace/consultants/{id}',
                'http_method'     => 'GET'
            )
        );

        ApiEndpoint::create(
            array(
                'name'            => 'get-consultant-offices',
                'active'          =>  true,
                'api_id'          => $consultants->id,
                'route'           => '/api/v1/marketplace/consultants/{id}/offices',
                'http_method'     => 'GET'
            )
        );

        $consultant_read_scope = ApiScope::where('name','=',sprintf('%s/consultants/read',$current_realm))->first();

        $endpoint              = ApiEndpoint::where('name','=','get-consultants')->first();
        $endpoint->scopes()->attach($consultant_read_scope->id);

        $endpoint              = ApiEndpoint::where('name','=','get-consultant')->first();
        $endpoint->scopes()->attach($consultant_read_scope->id);

        $endpoint              = ApiEndpoint::where('name','=','get-consultant-offices')->first();
        $endpoint->scopes()->attach($consultant_read_scope->id);
    }
*/


}