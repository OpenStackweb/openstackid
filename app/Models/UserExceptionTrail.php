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
 * @package Models
 */
#[ORM\Table(name: 'user_exceptions_trail')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineUserExceptionTrailRepository::class)]
class UserExceptionTrail extends BaseEntity {
    /**
     * @var string
     */
    #[ORM\Column(name: 'exception_type', type: 'string')]
    private $exception_type;

    /**
     * @var string
     */
    #[ORM\Column(name: 'from_ip', type: 'string')]
    private $from_ip;

    /**
     * @var string
     */
    #[ORM\Column(name: 'stack_trace', type: 'string')]
    private $stack_trace;
    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Auth\User::class)]
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
    public function getStackTrace(): string
    {
        return $this->stack_trace;
    }

    /**
     * @param string $stack_trace
     */
    public function setStackTrace(string $stack_trace): void
    {
        $this->stack_trace = $stack_trace;
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
     * @param $name
     * @return mixed
     */
    public function __get($name) {
        return $this->{$name};
    }
}