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

#[ORM\Table(name: 'two_factor_audit_log')]
#[ORM\Entity(repositoryClass: \App\Repositories\DoctrineTwoFactorAuditLogRepository::class)]
class TwoFactorAuditLog extends BaseEntity
{
    public const EventChallengeIssued = 'challenge_issued';
    public const EventChallengeSucceeded = 'challenge_succeeded';
    public const EventChallengeFailed = 'challenge_failed';
    public const EventEnrollmentChanged = 'enrollment_changed';
    public const EventDeviceTrusted = 'device_trusted';
    public const EventDeviceRevoked = 'device_revoked';
    public const EventRecoveryUsed = 'recovery_used';
    public const EventSettingsChanged = 'settings_changed';
    public const EventRecoveryCodesGenerated = 'recovery_codes_generated';

    public const MethodEmailOtp = 'email_otp';
    public const MethodSmsOtp = 'sms_otp';
    public const MethodTotp = 'totp';
    public const MethodPasskey = 'passkey';
    public const MethodRecovery = 'recovery';


    private const ALLOWED_EVENT_TYPES = [
        self::EventChallengeIssued,
        self::EventChallengeSucceeded,
        self::EventChallengeFailed,
        self::EventEnrollmentChanged,
        self::EventDeviceTrusted,
        self::EventDeviceRevoked,
        self::EventRecoveryUsed,
        self::EventSettingsChanged,
        self::EventRecoveryCodesGenerated,
    ];

    private const ALLOWED_METHODS = [
        self::MethodEmailOtp,
        self::MethodSmsOtp,
        self::MethodTotp,
        self::MethodPasskey,
        self::MethodRecovery,
    ];

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \Auth\User::class)]
    private $user;

    #[ORM\Column(name: 'event_type', type: 'string', length: 64)]
    private $event_type;

    #[ORM\Column(name: 'method', type: 'string', length: 32)]
    private $method;

    #[ORM\Column(name: 'ip_address', type: 'string', length: 45)]
    private $ip_address;

    #[ORM\Column(name: 'user_agent', type: 'text')]
    private $user_agent;

    #[ORM\Column(name: 'metadata', type: 'json', nullable: true)]
    private $metadata;


    public function __construct()
    {
        parent::__construct();
        $this->metadata = null;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getEventType(): string
    {
        return $this->event_type;
    }

    public function setEventType(string $value): void
    {
        if (!in_array($value, self::ALLOWED_EVENT_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported 2FA audit event type.');
        }
        $this->event_type = $value;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $value): void
    {
        if (!in_array($value, self::ALLOWED_METHODS, true)) {
            throw new \InvalidArgumentException('Unsupported 2FA audit method.');
        }
        $this->method = $value;
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

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $value): void
    {
        $this->metadata = $value;
    }
}