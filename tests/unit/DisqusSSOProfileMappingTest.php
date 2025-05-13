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
    public function testDisqusSSOProfilePersistence()
    {
        $slug = 'poc_disqus';

        $disqus_profile = new DisqusSSOProfile();
        $disqus_profile->setForumSlug($slug);
        $disqus_profile->setPublicKey("PUBLIC_KEY");
        $disqus_profile->setSecretKey("SECRET_KEY");

        EntityManager::persist($disqus_profile);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(DisqusSSOProfile::class);
        $found_disqus_profile = $repo->find($disqus_profile->getId());

        $this->assertInstanceOf(DisqusSSOProfile::class, $found_disqus_profile);
        $this->assertEquals($slug, $found_disqus_profile->getForumSlug());
    }
}
