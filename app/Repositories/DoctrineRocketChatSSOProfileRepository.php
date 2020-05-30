<?php namespace App\Repositories;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Models\Repositories\IRocketChatSSOProfileRepository;
use App\Models\SSO\RocketChatSSOProfile;
/**
 * Class DoctrineRocketChatSSOProfileRepository
 * @package App\Repositories
 */
final class DoctrineRocketChatSSOProfileRepository extends ModelDoctrineRepository
implements IRocketChatSSOProfileRepository
{

    /**
     * @inheritDoc
     */
    protected function getBaseEntity()
    {
        return RocketChatSSOProfile::class;
    }

    /**
     * @param string $forum_slug
     * @return RocketChatSSOProfile|null
     */
    public function getByForumSlug(string $forum_slug): ?RocketChatSSOProfile
    {
        return $this->findOneBy([
            'forum_slug' => trim($forum_slug)
        ]);
    }
}