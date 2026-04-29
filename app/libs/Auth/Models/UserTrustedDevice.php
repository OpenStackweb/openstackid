<?php
namespace App\libs\Auth\Models;
/**
 * Copyright 2026 OpenStack Foundation
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
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'user_trusted_devices')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineUserTrustedDeviceRepository::class)]
#[ORM\UniqueConstraint(name: 'utd_user_device_uniq', columns: ['user_id', 'device_identifier'])]
#[ORM\HasLifecycleCallbacks]
class UserTrustedDevice extends BaseEntity
{
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \Auth\User::class)]
    private $user;

    #[ORM\Column(name: 'device_identifier', type: 'string', length: 255)]
    private $device_identifier;

    #[ORM\Column(name: 'device_name', type: 'string', length: 255)]
    private $device_name;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45)]
    private $ip_address;

    #[ORM\Column(name: 'user_agent', type: 'text')]
    private $user_agent;

    #[ORM\Column(name: 'trusted_at', type: 'datetime')]
    private $trusted_at;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private $expires_at;

    #[ORM\Column(name: 'last_seen_at', type: 'datetime')]
    private $last_seen_at;

    #[ORM\Column(name: 'is_revoked', type: 'boolean', options: ['default' => 0])]
    private $is_revoked;

    public function __construct()
    {
        parent::__construct();
        $this->is_revoked = false;
    }

    public function getUser(): User
    {
        return $this->user;
    }
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getDeviceIdentifier(): string
    {
        return $this->device_identifier;
    }
    public function setDeviceIdentifier(string $value): void
    {
        $this->device_identifier = $value;
    }

    public function getDeviceName(): string
    {
        return $this->device_name;
    }
    public function setDeviceName(string $value): void
    {
        $this->device_name = $value;
    }

    public function getIpAddress(): string
    {
        return $this->ip_address;
    }
    public function setIpAddress(string $value): void
    {
        $this->ip_address = $value;
    }

    public function getUserAgent(): string
    {
        return $this->user_agent;
    }
    public function setUserAgent(string $value): void
    {
        $this->user_agent = $value;
    }

    public function getTrustedAt(): \DateTime
    {
        return $this->trusted_at;
    }
    public function setTrustedAt(\DateTime $value): void
    {
        $this->trusted_at = $value;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expires_at;
    }
    public function setExpiresAt(\DateTime $value): void
    {
        $this->expires_at = $value;
    }

    public function getLastSeenAt(): \DateTime
    {
        return $this->last_seen_at;
    }
    public function setLastSeenAt(\DateTime $value): void
    {
        $this->last_seen_at = $value;
    }

    public function isRevoked(): bool
    {
        return (bool) $this->is_revoked;
    }
    public function setIsRevoked(bool $value): void
    {
        $this->is_revoked = $value;
    }
}