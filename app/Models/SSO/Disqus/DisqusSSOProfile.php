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
#[ORM\Table(name: 'sso_disqus_profile')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineDisqusSSOProfileRepository::class)]
class DisqusSSOProfile extends BaseEntity
{
    /**
     * @var string
     */
    #[ORM\Column(name: 'forum_slug', type: 'string')]
    private $forum_slug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'public_key', type: 'string')]
    private $public_key;

    /**
     * @var string
     */
    #[ORM\Column(name: 'secret_key', type: 'string')]
    private $secret_key;

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
    public function getPublicKey(): string
    {
        return $this->public_key;
    }

    /**
     * @param string $public_key
     */
    public function setPublicKey(string $public_key): void
    {
        $this->public_key = $public_key;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secret_key;
    }

    /**
     * @param string $secret_key
     */
    public function setSecretKey(string $secret_key): void
    {
        $this->secret_key = $secret_key;
    }

}