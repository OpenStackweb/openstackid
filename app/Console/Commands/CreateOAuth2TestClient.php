<?php namespace App\Console\Commands;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Models\OAuth2\Factories\ClientFactory;
use Auth\User;
use Illuminate\Console\Command;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\ApiScope;
use Models\OAuth2\Client;
use OAuth2\Models\IClient;
use OAuth2\OAuth2Protocol;

/**
 * Class CreateOAuth2TestClient
 *
 * Creates (idempotently) the confidential OAuth2 client the e2e suite
 * authorizes against (tests/e2e/tests/oauth2/auth-code-flow.spec.ts).
 *
 * database/seeds/TestSeeder.php already seeds a client with this exact
 * client_id, but only as a side effect of truncating and rebuilding the
 * ENTIRE users/groups/oauth2_client tables from scratch - correct for
 * PHPUnit's isolated per-suite runs, but destructive if run against a
 * shared CI/dev database that also has other fixtures (e.g. the
 * idp:create-super-admin/idp:create-raw-user users this same workflow
 * seeds). This command creates only the one client, without touching
 * anything else.
 *
 * @package App\Console\Commands
 */
class CreateOAuth2TestClient extends Command
{
    protected $signature = 'idp:create-oauth2-test-client';

    protected $description = 'Create the confidential OAuth2 client the e2e oauth2 suite authorizes against (idempotent)';

    private const CLIENT_ID = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
    private const CLIENT_SECRET = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';
    private const REDIRECT_URI = 'https://www.test.com/oauth2';
    private const OWNER_EMAIL = 'oauth2-test-client-owner@test.com';

    public function handle(): int
    {
        $repository = EntityManager::getRepository(Client::class);
        $client = $repository->findOneBy(['client_id' => self::CLIENT_ID]);

        if (is_null($client)) {
            // The consent screen's getDeveloperEmail() dereferences the
            // client's owner unconditionally - a client without one 500s
            // as soon as a real login reaches /accounts/user/consent.
            $owner = EntityManager::getRepository(User::class)->findOneBy(['email' => self::OWNER_EMAIL]);
            if (is_null($owner)) {
                $owner = new User();
                $owner->setEmail(self::OWNER_EMAIL);
                $owner->verifyEmail();
                $owner->setPassword('1Qaz2wsx!');
                $owner->setFirstName(self::OWNER_EMAIL);
                $owner->setLastName(self::OWNER_EMAIL);
                $owner->setIdentifier(self::OWNER_EMAIL);
                EntityManager::persist($owner);
                EntityManager::flush();
            }

            $client = ClientFactory::build([
                'app_name'                   => 'oauth2_test_app',
                'app_description'            => 'oauth2_test_app',
                'client_id'                  => self::CLIENT_ID,
                'client_secret'              => self::CLIENT_SECRET,
                'client_type'                => IClient::ClientType_Confidential,
                'application_type'           => IClient::ApplicationType_Web_App,
                'token_endpoint_auth_method' => OAuth2Protocol::TokenEndpoint_AuthMethod_ClientSecretBasic,
                'owner'                      => $owner,
                'rotate_refresh_token'       => true,
                'use_refresh_token'          => true,
                'redirect_uris'              => self::REDIRECT_URI,
            ]);
            EntityManager::persist($client);
            EntityManager::flush();
            $this->info('Created client: ' . self::CLIENT_ID);
        } else {
            $this->info('Client already exists: ' . self::CLIENT_ID);
        }

        $scope = EntityManager::getRepository(ApiScope::class)->findOneBy(['name' => 'profile']);
        if (is_null($scope)) {
            $this->error("api scope 'profile' not found - run php artisan db:seed first");
            return 1;
        }

        $client->addScope($scope);
        EntityManager::persist($client);
        EntityManager::flush();

        return 0;
    }
}
