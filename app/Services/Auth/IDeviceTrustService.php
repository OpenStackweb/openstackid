<?php namespace App\Services\Auth;
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

use Auth\User;

/**
 * Interface IDeviceTrustService
 * @package App\Services\Auth
 */
interface IDeviceTrustService
{
    /**
     * Checks whether the device identified by the given cookie token is trusted for the user.
     * Updates last_seen_at on a valid match.
     */
    public function isDeviceTrusted(User $user, ?string $cookieToken): bool;

    /**
     * Marks the current device as trusted for the user.
     * Returns the raw 128-character hex token to be stored in the cookie.
     * The SHA-256 hash of the token (not the raw token) is persisted.
     */
    public function trustDevice(User $user, string $userAgent, string $ipAddress): string;

    /**
     * Revokes all trusted devices for the given user.
     */
    public function removeTrustedDevices(User $user): void;

    /**
     * Returns the SHA-256 hash of the given token used as the stored device identifier.
     */
    public function generateDeviceIdentifier(string $token): string;
}
