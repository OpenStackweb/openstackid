<?php namespace App\libs\Auth\Models;
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
use Auth\User;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineSpamEstimatorFeedRepository")
 * @ORM\Table(name="users_spam_estimator_feed")
 * Class SpamEstimatorFeed
 * @package App\libs\Auth\Models
 */
class SpamEstimatorFeed extends BaseEntity
{
    /**
     * @ORM\Column(name="first_name", type="string")
     * @var string
     */
    private $first_name;

    /**
     * @ORM\Column(name="last_name", type="string")
     * @var string
     */
    private $last_name;

    /**
     * @ORM\Column(name="email", type="string")
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="bio", nullable=true, type="string")
     * @var string
     */
    private $bio;

    /**
     * @ORM\Column(name="spam_type", nullable=false, type="string")
     * @var string
     */
    private $spam_type;

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName(string $last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * @param string $bio
     */
    public function setBio(string $bio): void
    {
        $this->bio = $bio;
    }

    /**
     * @return string
     */
    public function getSpamType(): ?string
    {
        return $this->spam_type;
    }

    /**
     * @param string $spam_type
     */
    public function setSpamType(string $spam_type): void
    {
        $this->spam_type = $spam_type;
    }

    /**
     * @param User $user
     * @param string $spam_type
     * @return SpamEstimatorFeed
     */
    public static function buildFromUser(User $user, string $spam_type){
        $feed = new SpamEstimatorFeed;
        $feed->spam_type  = $spam_type;
        $feed->email      = $user->getEmail();
        $feed->first_name = $user->getFirstName();
        $feed->last_name  = $user->getLastName();
        $feed->bio        = $user->getBio();
        return $feed;
    }
}