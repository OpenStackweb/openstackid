<?php namespace Tests\unit;

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
use Models\OAuth2\Client;
use OAuth2\Models\IClient;
use Tests\BrowserKitTestCase;

/**
 * Class ClientFactoryTest
 * @package Tests\unit
 */
class ClientFactoryTest extends BrowserKitTestCase
{
    public function testPopulateTrimsEachUriListItem()
    {
        // The pre-hardening create() persisted "a, b" lists verbatim: URL\Normalizer preserves a
        // leading space, so populate()'s per-item normalization alone does not canonicalize the list.
        // Every write path going through the factory (including seeders calling build() directly,
        // which bypass the service-layer validation) must store canonical comma-separated lists so
        // the anchored cross-client scheme-uniqueness LIKE keeps matching real list-item boundaries.
        $client = ClientFactory::populate(new Client, [
            'application_type'          => IClient::ApplicationType_Native,
            'post_logout_redirect_uris' => 'https://web.example.com/logout, myapp://cb',
            'redirect_uris'             => 'https://web.example.com/cb, otherapp://cb',
        ]);

        $this->assertStringNotContainsString(', ', implode(',', $client->getPostLogoutUris()));
        $this->assertStringNotContainsString(', ', $client->getRawRedirectUris());
    }
}
