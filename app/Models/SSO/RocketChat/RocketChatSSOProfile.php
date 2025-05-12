<?php namespace App\Models\SSO;
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
use App\Models\Utils\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package  App\Models\SSO
 */
#[ORM\Table(name: 'sso_rocket_chat_profile')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineRocketChatSSOProfileRepository::class)]
class RocketChatSSOProfile extends BaseEntity
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'forum_slug', type: 'string')]
    private $forum_slug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'base_url', type: 'string')]
    private $base_url;

    /**
     * @var string
     */
    #[ORM\Column(name: 'service_name', type: 'string')]
    private $service_name;

    /**
     * @return string
     */
    public function getForumSlug(): string
    {
        return $this->forum_slug;
    }

    /**
     * @param string $forum_slug
     */
    public function setForumSlug(string $forum_slug): void
    {
        $this->forum_slug = $forum_slug;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    /**
     * @param string $base_url
     */
    public function setBaseUrl(string $base_url): void
    {
        $this->base_url = $base_url;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->service_name;
    }

    /**
     * @param string $service_name
     */
    public function setServiceName(string $service_name): void
    {
        $this->service_name = $service_name;
    }

}