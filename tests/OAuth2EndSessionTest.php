<?php namespace Tests;
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

use LaravelDoctrine\ORM\Facades\EntityManager;
use Models\OAuth2\Client;

/**
 * Class OAuth2EndSessionTest
 * NOTE: deliberately NOT placed in OAuth2ProtocolTestCase.php - that file's *TestCase.php suffix
 * keeps it OUT of the Application Test Suite (PHPUnit only auto-discovers *Test.php), so a test
 * added there would never run in CI.
 * @package Tests
 */
final class OAuth2EndSessionTest extends OpenStackIDBaseTestCase
{
    public function testEndSessionRedirectsVerbatimToAuthorityLessPostLogoutUri()
    {
        // RFC 8252 SS7.1 authority-less URIs (com.example.app:/logout) fail Laravel's
        // UrlGenerator::isValidUrl() (FILTER_VALIDATE_URL-based), so Redirect::to() inside
        // IndirectResponseQueryStringStrategy used to treat the approved post-logout target as a
        // RELATIVE path and prefix the site URL (Location: http://<idp>/com.example.app:/logout) -
        // corrupting the redirect at the emitter even once the runtime allow-gates accept the
        // authority-less form. The Location header must carry the registered URI verbatim, with the
        // state round-tripped on the query string.
        $client = EntityManager::getRepository(Client::class)->findOneBy(['app_name' => 'oauth2_native_app']);
        $client->setPostLogoutRedirectUris('com.example.app:/logout');
        EntityManager::persist($client);
        EntityManager::flush();

        $this->call('GET', '/oauth2/end-session', [
            'client_id'                => $client->getClientId(),
            'post_logout_redirect_uri' => 'com.example.app:/logout',
            'state'                    => 'xyz',
        ]);

        $this->assertResponseStatus(302);
        $this->assertEquals('com.example.app:/logout?state=xyz', $this->response->headers->get('Location'));
    }
}
