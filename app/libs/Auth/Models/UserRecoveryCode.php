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
use App\Repositories\DoctrineUserRecoveryCodeRepository;
use models\exceptions\ValidationException;

#[ORM\Table(name: 'user_recovery_codes')]
#[ORM\Entity(repositoryClass: DoctrineUserRecoveryCodeRepository::class)]
class UserRecoveryCode extends BaseEntity
{
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    #[ORM\Column(name: 'code_hash', type: 'string', length: 72)]
    private $code_hash;

    #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
    private $used_at;

    public function __construct()
    {
        parent::__construct();
        $this->used_at = null;
    }


    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getCodeHash(): string
    {
        return $this->code_hash;
    }

    public function setCodeHash(string $value): void
    {
        $info = password_get_info($value);
        if (($info['algo'] ?? null) !== PASSWORD_BCRYPT) {
            throw new \InvalidArgumentException('code_hash must be a bcrypt hash');
        }
        $this->code_hash = $value;
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->used_at;
    }


    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function markUsed(): void
    {
        if ($this->used_at !== null) {
            throw new ValidationException('Recovery code already used at ' . $this->used_at->format(\DateTime::ATOM));
        }
        $this->used_at = new \DateTime('now', new \DateTimeZone('UTC'));
    }

}