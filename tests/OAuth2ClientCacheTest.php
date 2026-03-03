<?php namespace Tests;
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
use Illuminate\Support\Facades\App;
use OAuth2\Repositories\IClientRepository;
/**
 * Class OAuth2ClientCacheTest
 */
final class OAuth2ClientCacheTest extends BrowserKitTestCase
{
    /**
     * @var IClientRepository
     */
    private IClientRepository $repo;

    protected function prepareForTests(): void
    {
        parent::prepareForTests();
        $this->repo = App::make(IClientRepository::class);
    }

    /**
     * A client ID containing '/' must be retrievable without throwing
     * InvalidArgumentException due to PSR-6 reserved characters in the
     * cache key. A second call must also succeed, confirming the cached
     * entry is readable.
     */
    public function testGetClientByIdCacheableWithReservedCharactersIsRetrievable(): void
    {
        $client_id = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';

        // First call: fetches from DB and writes the hashed cache key.
        $client = $this->repo->getClientByIdCacheable($client_id);
        $this->assertNotNull($client);
        $this->assertEquals($client_id, $client->getClientId());

        // Second call: reads from result cache — must not throw on the hashed key.
        $client_cached = $this->repo->getClientByIdCacheable($client_id);
        $this->assertNotNull($client_cached);
        $this->assertEquals($client_id, $client_cached->getClientId());
    }

    /**
     * Two distinct client IDs that both contain '/' must hash to different
     * cache keys, so that each call returns the correct client and the
     * md5 hashing does not accidentally collapse them to the same entry.
     */
    public function testGetClientByIdCacheableDistinctReservedCharacterIdsReturnDifferentClients(): void
    {
        $client_id_a = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
        $client_id_b = 'Jiz87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x2.openstack.client';

        $client_a = $this->repo->getClientByIdCacheable($client_id_a);
        $client_b = $this->repo->getClientByIdCacheable($client_id_b);

        $this->assertNotNull($client_a);
        $this->assertNotNull($client_b);
        $this->assertEquals($client_id_a, $client_a->getClientId());
        $this->assertEquals($client_id_b, $client_b->getClientId());
        $this->assertNotEquals($client_a->getClientId(), $client_b->getClientId());
    }
}
