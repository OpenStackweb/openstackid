<?php namespace App\Http\Controllers\Traits;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Utils\IPHelper;

/**
 * Trait MFACookieManager
 *
 * Reads and queues the trusted-device cookie. All trusted-device persistence
 * and validation lives in IDeviceTrustService — this trait contains NO Doctrine,
 * repository, or device-lookup logic; it only moves the raw token in and out of
 * the HTTP cookie.
 *
 * The consuming controller MUST expose an IDeviceTrustService instance as
 * $this->device_trust_service.
 *
 * @package App\Http\Controllers\Traits
 */
trait MFACookieManager
{
    /**
     * Reads the raw trusted-device token from the request cookie.
     *
     * @return string|null
     */
    protected function getCookieToken(): ?string
    {
        return Request::cookie(Config::get('two_factor.cookie_name', 'device_trust_token'));
    }

    /**
     * Persists a trusted-device record (via IDeviceTrustService) and queues a
     * secure, HttpOnly cookie carrying the raw token for the configured lifetime.
     *
     * @param User $user
     * @return void
     */
    protected function queueDeviceTrustCookie(User $user): void
    {
        $rawToken = $this->device_trust_service->trustDevice
        (
            $user,
            Request::header('User-Agent') ?? '',
            IPHelper::getUserIp()
        );

        $name = Config::get('two_factor.cookie_name', 'device_trust_token');
        $lifetimeMinutes = intval(Config::get('two_factor.device_trust_lifetime_days', 30)) * 24 * 60;
        $path = Config::get('session.path');
        $domain = Config::get('session.domain');
        $secure = true;
        $httpOnly = true;
        $raw = false;
        $sameSite = 'lax';

        // Same order as \Illuminate\Cookie\CookieJar::make()
        Cookie::queue
        (
            $name,
            $rawToken, // value
            $lifetimeMinutes,
            $path,
            $domain,
            $secure,
            $httpOnly,
            $raw,
            $sameSite

        );
    }
}
