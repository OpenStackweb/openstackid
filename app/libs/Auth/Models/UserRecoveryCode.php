<?php namespace App\libs\Auth\Models;
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

use Auth\User;
use Doctrine\ORM\Mapping AS ORM;

#[ORM\Table(name: 'user_recovery_codes')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineUserRecoveryCodeRepository::class)]
class UserRecoveryCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer', unique: true, nullable: false)]
    protected $id;

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \Auth\User::class)]
    private $user;

    #[ORM\Column(name: 'code_hash', type: 'string', length: 255)]
    private $code_hash;

    #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
    private $used_at;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private $created_at;

    public function __construct()
    {
        $this->created_at = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->used_at = null;
    }

    public function getId(): int { return (int) $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): void { $this->user = $user; }

    public function getCodeHash(): string { return $this->code_hash; }
    public function setCodeHash(string $value): void { $this->code_hash = $value; }

    public function getUsedAt(): ?\DateTime { return $this->used_at; }
    public function setUsedAt(?\DateTime $value): void { $this->used_at = $value; }

    public function getCreatedAt(): \DateTime { return $this->created_at; }

    public function isUsed(): bool { return !is_null($this->used_at); }

    public function markUsed(): void
    {
        $this->used_at = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __get($name) { return $this->{$name}; }
}
