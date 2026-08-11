<?php namespace App\Services\Auth;
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

/**
 * Interface ITwoFactorAuditService
 * @package App\Services\Auth
 */
interface ITwoFactorAuditService
{
    /**
     * Persist a TwoFactorAuditLog record and emit corresponding OTLP attributes.
     *
     * @param User        $user      The user the event relates to.
     * @param string      $eventType One of the TwoFactorAuditLog::Event* constants.
     * @param string      $method    One of the TwoFactorAuditLog::Method* constants.
     * @param string      $ipAddress Client IP address (IPv4 or IPv6).
     * @param array|null  $metadata  Optional structured context; stored as JSON.
     *
     * @throws \InvalidArgumentException if $eventType or $method is not in the allowed set.
     */
    public function log(User $user, string $eventType, string $method, string $ipAddress, ?array $metadata = null): void;
}
