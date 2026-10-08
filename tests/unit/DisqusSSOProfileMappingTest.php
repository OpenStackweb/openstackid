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

use App\Models\SSO\DisqusSSOProfile;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;

/**
 * Class DisqusSSOProfileMappingTest
 * @package Tests\unit
 */
class DisqusSSOProfileMappingTest extends BrowserKitTestCase
{
    private string $slug = 'poc_disqus_test';

    protected function setUp(): void
    {
        parent::setUp();
        // Remove any leftover record from a previous run so the test is idempotent.
        $repo = EntityManager::getRepository(DisqusSSOProfile::class);
        $existing = $repo->findOneBy(['forum_slug' => $this->slug]);
        if ($existing) {
            EntityManager::remove($existing);
            EntityManager::flush();
        }
    }

    protected function tearDown(): void
    {
        // Clean up the record created by the test so subsequent runs start fresh.
        $repo = EntityManager::getRepository(DisqusSSOProfile::class);
        $existing = $repo->findOneBy(['forum_slug' => $this->slug]);
        if ($existing) {
            EntityManager::remove($existing);
            EntityManager::flush();
        }
        parent::tearDown();
    }

    public function testDisqusSSOProfilePersistence()
    {
        $disqus_profile = new DisqusSSOProfile();
        $disqus_profile->setForumSlug($this->slug);
        $disqus_profile->setPublicKey("PUBLIC_KEY");
        $disqus_profile->setSecretKey("SECRET_KEY");

        EntityManager::persist($disqus_profile);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(DisqusSSOProfile::class);
        $found_disqus_profile = $repo->find($disqus_profile->getId());

        $this->assertInstanceOf(DisqusSSOProfile::class, $found_disqus_profile);
        $this->assertEquals($this->slug, $found_disqus_profile->getForumSlug());
    }
}
