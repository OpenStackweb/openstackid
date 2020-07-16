<?php namespace App\Models\SSO\StreamChat;
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
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineStreamChatSSOProfileRepository")
 * @ORM\Table(name="sso_stream_chat_profile")
 * Class StreamChatSSOProfile
 * @package  App\Models\SSO
 */
class StreamChatSSOProfile extends BaseEntity
{
    /**
     * @ORM\Column(name="forum_slug", type="string")
     * @var string
     */
    private $forum_slug;

    /**
     * @ORM\Column(name="api_key", type="string")
     * @var string
     */
    private $api_key;

    /**
     * @ORM\Column(name="api_secret", type="string")
     * @var string
     */
    private $api_secret;

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
    public function getApiKey(): string
    {
        return $this->api_key;
    }

    /**
     * @param string $api_key
     */
    public function setApiKey(string $api_key): void
    {
        $this->api_key = $api_key;
    }

    /**
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->api_secret;
    }

    /**
     * @param string $api_secret
     */
    public function setApiSecret(string $api_secret): void
    {
        $this->api_secret = $api_secret;
    }
}