<?php namespace Tests;
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
use Auth\User;
use Models\OAuth2\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Services\OAuth2\ClientService;
use Utils\Services\ILockManagerService;
/**
 * Class ClientApiTest
 */
class ClientApiTest extends BrowserKitTestCase {

    private $current_realm;

    private $current_host;

    protected function prepareForTests():void
    {
        parent::prepareForTests();
        $this->withoutMiddleware();
        $this->current_realm = Config::get('app.url');
        $parts               = parse_url($this->current_realm);
        $this->current_host  = $parts['host'];

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $this->be($user);
        Session::start();
    }

    public function testGetById(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_test_app']);

        $response = $this->action("GET", "Api\\ClientApiController@get",
            $parameters = array('id' => $client->id),
            [],
            [],
            []);

        $content         = $response->getContent();
        $response_client = json_decode($content);

        $this->assertResponseStatus(200);
        $this->assertTrue($response_client->id === $client->id);
    }

    public function testGetByPage(){

        $response = $this->action("GET", "Api\\ClientApiController@getAll",
            $parameters = array('page' => 1,'per_page'=>10),
            [],
            [],
            []);

        $content         = $response->getContent();
        $this->assertResponseStatus(200);
        $list            = json_decode($content);
        $this->assertTrue(isset($list->total) && intval($list->total)>0);
    }

    public function testCreate(){

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $data = array(
            'user_id'            => $user->id,
            'app_name'           => 'test_app',
            'app_description'    => 'test app',
            'website'            => 'http://www.test.com',
            'application_type'   => IClient::ApplicationType_Native
        );

        $response = $this->action("POST", "Api\\ClientApiController@create",
            $data,
            [],
            [],
            []);

        $content       = $response->getContent();
        $json_response = json_decode($content);

        $this->assertResponseStatus(201);
        $this->assertTrue(isset($json_response->client_id) && !empty($json_response->client_id));
    }

    public function testUpdateNativeClientAcceptsCustomSchemePostLogoutUrisAndAllowedOrigins(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

        $data = array(
            'id'                        => $client->id,
            'application_type'          => IClient::ApplicationType_Native,
            'post_logout_redirect_uris' => 'myapp://callback/logout',
            'allowed_origins'           => 'https://web.example.com,myapp://callback',
        );

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            $data,
            [],
            [],
            []);

        $this->assertResponseStatus(201);

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);
        $this->assertTrue(str_contains(implode(',', $client->getPostLogoutUris()), 'myapp://callback/logout'));
        $this->assertTrue(str_contains($client->getRawClientAllowedOrigins(), 'myapp://callback'));
        $this->assertTrue(str_contains($client->getRawClientAllowedOrigins(), 'https://web.example.com'));
    }

    public function testUpdateNativeClientRejectsFtpSchemeOnPostLogoutUris(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

        $data = array(
            'id'                        => $client->id,
            'application_type'          => IClient::ApplicationType_Native,
            'post_logout_redirect_uris' => 'ftp://foo/bar',
        );

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            $data,
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testUpdateNativeClientRejectsDangerousAndHttpSchemes(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

        foreach (['javascript://x%0aalert(1)', 'data://text/html', 'intent://scan/#Intent;end', 'http://insecure.example.com/cb'] as $bad_uri) {
            $data = array(
                'id'                        => $client->id,
                'application_type'          => IClient::ApplicationType_Native,
                'post_logout_redirect_uris' => $bad_uri,
            );

            $response = $this->action("PUT", "Api\\ClientApiController@update",
                $data,
                [],
                [],
                []);

            $this->assertResponseStatus(412);
        }
    }

    public function testUpdateNativeClientRejectsCustomSchemeAlreadyRegisteredByAnotherClient(){

        $client1 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);
        $client2 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app2']);

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'                        => $client1->id,
                'application_type'          => IClient::ApplicationType_Native,
                'post_logout_redirect_uris' => 'sharedscheme://callback/logout',
            ),
            [],
            [],
            []);
        $this->assertResponseStatus(201);

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'               => $client2->id,
                'application_type' => IClient::ApplicationType_Native,
                'allowed_origins'  => 'sharedscheme://other',
            ),
            [],
            [],
            []);
        $this->assertResponseStatus(412);
    }

    public function testUpdateNativeClientAllowsSchemeThatIsSubstringOfAnotherClientsScheme(){

        // oauth2_native_app is seeded with redirect_uris = androipapp://oidc_endpoint_callback (TestSeeder).
        // 'roipapp' is a literal substring of 'androipapp', but a DIFFERENT scheme - registering it on another
        // client must not be rejected as a collision (a plain '%scheme://%' LIKE would false-positive here).
        $client2 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app2']);

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'               => $client2->id,
                'application_type' => IClient::ApplicationType_Native,
                'allowed_origins'  => 'roipapp://cb',
            ),
            [],
            [],
            []);

        $this->assertResponseStatus(201);
    }

    public function testUpdateNativeClientRejectsDangerousSchemeOnRedirectUris(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

        foreach (['javascript://x%0aalert(1)', 'intent://scan/#Intent;end'] as $bad_uri) {
            $data = array(
                'id'                => $client->id,
                'application_type'  => IClient::ApplicationType_Native,
                'redirect_uris'     => $bad_uri,
            );

            $response = $this->action("PUT", "Api\\ClientApiController@update",
                $data,
                [],
                [],
                []);

            $this->assertResponseStatus(412);
        }
    }

    public function testUpdateNativeClientRejectsDangerousAndHttpSchemesOnAllowedOrigins(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

        foreach (['javascript://x%0aalert(1)', 'intent://scan/#Intent;end', 'itms-services://x/?action=download-manifest', 'http://insecure.example.com'] as $bad_uri) {
            $data = array(
                'id'                => $client->id,
                'application_type'  => IClient::ApplicationType_Native,
                'allowed_origins'   => $bad_uri,
            );

            $response = $this->action("PUT", "Api\\ClientApiController@update",
                $data,
                [],
                [],
                []);

            $this->assertResponseStatus(412);
        }
    }

    public function testCreateNativeClientRejectsDangerousSchemeOnPostLogout(){

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $data = array(
            'user_id'                   => $user->id,
            'app_name'                  => 'native_dangerous_scheme_app',
            'app_description'           => 'native app with dangerous scheme',
            'application_type'          => IClient::ApplicationType_Native,
            'post_logout_redirect_uris' => 'javascript://x%0aalert(1)',
        );

        $response = $this->action("POST", "Api\\ClientApiController@create",
            $data,
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testCreateNativeClientRejectsDangerousSchemeOnRedirectUris(){

        // CodeRabbit PR #147 finding: create() validated allowed_origins/post_logout_redirect_uris for
        // dangerous schemes but never redirect_uris - a create payload could register javascript:// etc.
        // there and it would only ever be caught later by the runtime isUriAllowed() gate, not at write time.
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $data = array(
            'user_id'          => $user->id,
            'app_name'         => 'native_dangerous_redirect_uri_app',
            'app_description'  => 'native app with dangerous scheme on redirect_uris',
            'application_type' => IClient::ApplicationType_Native,
            'redirect_uris'    => 'javascript://x%0aalert(1)',
        );

        $response = $this->action("POST", "Api\\ClientApiController@create",
            $data,
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testCreateNativeClientRejectsRedirectUriSchemeAlreadyRegisteredByAnotherClient(){

        // CodeRabbit PR #147 finding, cross-client uniqueness half: create() never checked redirect_uris
        // scheme collisions either (only update() did, via a separate now-removed inline loop).
        $existing = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);
        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'               => $existing->id,
                'application_type' => IClient::ApplicationType_Native,
                'redirect_uris'    => 'createuniqueness://callback',
            ),
            [],
            [],
            []);
        $this->assertResponseStatus(201);

        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);
        $response = $this->action("POST", "Api\\ClientApiController@create",
            array(
                'user_id'          => $user->id,
                'app_name'         => 'native_duplicate_redirect_scheme_app',
                'app_description'  => 'native app registering an already-claimed redirect_uris scheme',
                'application_type' => IClient::ApplicationType_Native,
                'redirect_uris'    => 'createuniqueness://other',
            ),
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testUpdateJsClientRejectsCustomSchemeOnPostLogoutUrisAndAllowedOrigins(){

        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_test_app_public_2']);

        $data = array(
            'id'                        => $client->id,
            'application_type'          => IClient::ApplicationType_JS_Client,
            'post_logout_redirect_uris' => 'myapp://callback/logout',
        );

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            $data,
            [],
            [],
            []);

        $this->assertResponseStatus(412);

        $data = array(
            'id'               => $client->id,
            'application_type' => IClient::ApplicationType_JS_Client,
            'allowed_origins'  => 'myapp://callback',
        );

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            $data,
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testCreateNativeClientRejectsCustomSchemeWithLeadingSpaceInList(){

        // Regression: hasCustomSchemeRegisteredOnAnotherClientThan() anchors matches to "starts the
        // field" or "immediately follows a comma" with no tolerance for whitespace, and (before this
        // fix) create() applied zero request-level validation to redirect_uris/allowed_origins/
        // post_logout_redirect_uris (absent from getCreatePayloadValidationRules()), so a list with a
        // space after the comma reached storage verbatim - silently defeating the cross-client scheme
        // uniqueness check for that entry. Now that create() validates these fields with the same
        // custom_url_set rule update() already used, the malformed list is rejected outright before it
        // can ever reach storage.
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $response = $this->action("POST", "Api\\ClientApiController@create",
            array(
                'user_id'                   => $user->id,
                'app_name'                  => 'native_wspacebypass_app',
                'app_description'           => 'native app sending a list with a space after the comma',
                'application_type'          => IClient::ApplicationType_Native,
                'post_logout_redirect_uris' => 'https://web.example.com/logout, wspacebypass://callback/logout',
            ),
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testCreateNativeClientRejectsArrayValueForRedirectUrisCleanly(){

        // Regression: getCreatePayloadValidationRules() does not declare redirect_uris/allowed_origins/
        // post_logout_redirect_uris at all, so create() applies zero request-level validation to them -
        // any value, of any PHP type, reaches ClientService::create() untouched. There,
        // assertNativeCustomSchemesAllowed() calls explode(',', $payload[$field]); explode() requires a
        // string and throws a TypeError (not an Exception) on an array, which escapes every catch block in
        // APICRUDController::create() and surfaces as a generic 500 instead of the intended 412.
        $user = EntityManager::getRepository(User::class)->findOneBy(['identifier' => 'sebastian.marcet']);

        $response = $this->action("POST", "Api\\ClientApiController@create",
            array(
                'user_id'           => $user->id,
                'app_name'          => 'native_array_redirect_uri_app',
                'app_description'   => 'native app sending a non-string redirect_uris value',
                'application_type'  => IClient::ApplicationType_Native,
                'redirect_uris'     => ['myapp://callback', 'otherapp://callback'],
            ),
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testUpdateNativeClientRejectsSchemeAlreadyRegisteredInLegacySpaceSeparatedList(){

        // Legacy rows: before the create()-validation hardening in this branch, POST create persisted
        // URI lists verbatim (zero request-level validation), so a stored value can still contain
        // ", scheme://" (comma + single space - the JSON/forms list artifact). The anchored uniqueness
        // LIKE must tolerate exactly that artifact, or a second client can silently claim a scheme a
        // legacy row already holds - defeating the OS-level interception protection for those rows.
        // Simulate the legacy row via a direct entity write, bypassing service validation just like
        // the pre-hardening create() did.
        $client1 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);
        $client1->setPostLogoutRedirectUris('https://web.example.com/logout, legacyspaced://cb');
        EntityManager::persist($client1);
        EntityManager::flush();

        $client2 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app2']);

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'               => $client2->id,
                'application_type' => IClient::ApplicationType_Native,
                'redirect_uris'    => 'legacyspaced://other',
            ),
            [],
            [],
            []);

        $this->assertResponseStatus(412);
    }

    public function testUpdateNativeClientRejectsCustomSchemeRegistrationWhenAnotherIsInProgress(){

        // Closes the TOCTOU race in hasCustomSchemeRegisteredOnAnotherClientThan(): without a lock,
        // two concurrent create()/update() calls can both pass the "is this scheme already registered"
        // uniqueness check before either transaction commits, letting two different clients claim the
        // same custom scheme. create()/update() now serialize behind a single Redis-backed lock
        // (ILockManagerService). Simulate contention directly by pre-acquiring that same lock.
        $lock_manager = App::make(ILockManagerService::class);
        $lock_manager->acquireLock(ClientService::NATIVE_CUSTOM_SCHEME_REGISTRATION_LOCK, 5);

        try {
            $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

            $response = $this->action("PUT", "Api\\ClientApiController@update",
                array(
                    'id'                => $client->id,
                    'application_type'  => IClient::ApplicationType_Native,
                    'redirect_uris'     => 'lockcontention://callback',
                ),
                [],
                [],
                []);

            $this->assertResponseStatus(412);
        } finally {
            $lock_manager->releaseLock(ClientService::NATIVE_CUSTOM_SCHEME_REGISTRATION_LOCK);
        }
    }

    public function testUpdateNativeClientNotTouchingUriFieldsIgnoresLockContention(){

        // The TOCTOU lock only protects hasCustomSchemeRegisteredOnAnotherClientThan(), which only runs
        // when the payload touches redirect_uris/allowed_origins/post_logout_redirect_uris. A Native
        // client update that doesn't touch any of those (e.g. renaming app_name) must not contend with
        // an unrelated tenant's in-progress scheme registration - unlike the sibling test above, this
        // must succeed even while the lock is held.
        $lock_manager = App::make(ILockManagerService::class);
        $lock_manager->acquireLock(ClientService::NATIVE_CUSTOM_SCHEME_REGISTRATION_LOCK, 5);

        try {
            $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

            $response = $this->action("PUT", "Api\\ClientApiController@update",
                array(
                    'id'                => $client->id,
                    'application_type'  => IClient::ApplicationType_Native,
                    'app_description'   => 'updated description while another registration is in progress',
                ),
                [],
                [],
                []);

            $this->assertResponseStatus(201);
        } finally {
            $lock_manager->releaseLock(ClientService::NATIVE_CUSTOM_SCHEME_REGISTRATION_LOCK);
        }
    }

    public function testUpdateNativeClientRejectsSchemeAlreadyRegisteredInAuthorityLessForm(){

        // RFC 8252 SS7.1 authority-less registrations (com.example.app:/oauth2redirect) store the scheme
        // followed by ":/" instead of "://". The cross-client scheme-uniqueness LIKE must see that shape
        // too: the OS-level interception risk is about the SCHEME, regardless of which URI form either
        // client registered it in - so claiming "authlessscheme" via the authority-less form must block
        // another client from claiming it via the authority form (and vice versa).
        $client1 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'               => $client1->id,
                'application_type' => IClient::ApplicationType_Native,
                'redirect_uris'    => 'authlessscheme:/callback',
            ),
            [],
            [],
            []);
        $this->assertResponseStatus(201);

        $client2 = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app2']);

        $response = $this->action("PUT", "Api\\ClientApiController@update",
            array(
                'id'               => $client2->id,
                'application_type' => IClient::ApplicationType_Native,
                'redirect_uris'    => 'authlessscheme://other',
            ),
            [],
            [],
            []);
        $this->assertResponseStatus(412);
    }

}