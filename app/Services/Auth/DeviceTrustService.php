<?php
namespace App\Services\Auth;
/**
 * Copyright 2025 OpenStack Foundation
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

use App\libs\Auth\Models\UserTrustedDevice;
use Auth\Repositories\IUserTrustedDeviceRepository;
use Auth\User;
use DateTime;
use DateInterval;
use DateTimeZone;

/**
 * Class DeviceTrustService
 * @package App\Services\Auth
 */
final class DeviceTrustService implements IDeviceTrustService
{
    public function __construct(private readonly IUserTrustedDeviceRepository $repository)
    {
    }

    public function generateDeviceIdentifier(string $token): string
    {
        return hash('sha256', $token);
    }

    public function trustDevice(User $user, string $userAgent, string $ipAddress): string
    {
        $rawToken = bin2hex(random_bytes(64));

        $lifetimeDays = (int) config('two_factor.device_trust_lifetime_days', 30);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $expiresAt = clone $now;
        $expiresAt->add(new DateInterval("P{$lifetimeDays}D"));

        $device = new UserTrustedDevice();
        $device->setUser($user);
        $device->setDeviceIdentifier($this->generateDeviceIdentifier($rawToken));
        $device->setDeviceName(substr($userAgent, 0, 255));
        $device->setIpAddress($ipAddress);
        $device->setUserAgent($userAgent);
        $device->setTrustedAt($now);
        $device->setExpiresAt($expiresAt);
        $device->setLastSeenAt(clone $now);
        $device->setIsRevoked(false);

        $this->repository->add($device, true);

        return $rawToken;
    }

    public function isDeviceTrusted(User $user, ?string $cookieToken): bool
    {
        if (empty($cookieToken)) {
            return false;
        }

        $identifier = $this->generateDeviceIdentifier($cookieToken);
        $device = $this->repository->getByUserAndDeviceIdentifier($user, $identifier);

        if (!$device instanceof UserTrustedDevice || $device->isRevoked() || $device->isExpired()) {
            return false;
        }

        $device->setLastSeenAt(new DateTime('now', new DateTimeZone('UTC')));
        $this->repository->add($device, true);
        return true;
    }

    public function removeTrustedDevices(User $user): void
    {
        $this->repository->revokeAllForUser($user);
    }
}
