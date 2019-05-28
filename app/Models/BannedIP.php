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
 * @ORM\Entity(repositoryClass="App\Repositories\DoctrineBannedIPRepository")
 * @ORM\Table(name="banned_ips")
 * Class BannedIP
 * @package Models
 */
class BannedIP extends BaseEntity
{
    /**
     * @ORM\Column(name="exception_type", type="string")
     * @var string
     */
    private $exception_type;

    /**
     * @ORM\Column(name="ip", type="string")
     * @var string
     */
    private $ip;

    /**
     * @ORM\Column(name="hits", type="integer")
     * @var int
     */
    private $hits;

    /**
     * @ORM\ManyToOne(targetEntity="Auth\User", cascade={"persist"}, inversedBy="consents")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    private $user;

    /**
     * @return string
     */
    public function getExceptionType(): string
    {
        return $this->exception_type;
    }

    /**
     * @param string $exception_type
     */
    public function setExceptionType(string $exception_type): void
    {
        $this->exception_type = $exception_type;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ips
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return int
     */
    public function getHits(): int
    {
        return $this->hits;
    }

    /**
     * @param int $hits
     */
    public function setHits(int $hits): void
    {
        $this->hits = $hits;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function updateHits():int{
        $this->hits = $this->hits + 1 ;
        return $this->hits;
    }

    /**
     * @return bool
     */
    public function hasUser():bool{
        return $this->getUserId() > 0;
    }

    public function getUserId():int{
        try {
            return !is_null($this->user) ? $this->user->getId() : 0;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }

} 