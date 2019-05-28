<?php namespace Models;
/**
 * Copyright 2016 OpenStack Foundation
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="user_actions")
 * Class UserAction
 * @package Models
 */
class UserAction extends BaseEntity
{
    /**
     * @ORM\Column(name="realm", type="string")
     * @var string
     */
    private $realm;

    /**
     * @ORM\Column(name="from_ip", type="string")
     * @var string
     */
    private $from_ip;

    /**
     * @ORM\Column(name="user_action", type="string")
     * @var string
     */
    private $user_action;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", cascade={"persist"}, inversedBy="actions")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $owner;

    /**
     * @return string
     */
    public function getRealm(): string
    {
        return $this->realm;
    }

    public function hasRealm():bool
    {
        return !is_null($this->realm);
    }

    /**
     * @param string $realm
     */
    public function setRealm(string $realm): void
    {
        $this->realm = $realm;
    }

    /**
     * @return string
     */
    public function getFromIp(): string
    {
        return $this->from_ip;
    }

    /**
     * @param string $from_ip
     */
    public function setFromIp(string $from_ip): void
    {
        $this->from_ip = $from_ip;
    }

    /**
     * @return string
     */
    public function getUserAction(): string
    {
        return $this->user_action;
    }

    /**
     * @param string $user_action
     */
    public function setUserAction(string $user_action): void
    {
        $this->user_action = $user_action;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     */
    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

}