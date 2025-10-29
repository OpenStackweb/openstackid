<?php namespace App\libs\Auth;
/**
 * Copyright 2021 OpenStack Foundation
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

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Class SocialLoginProviders
 * @package App\libs\Auth
 */
final class SocialLoginProviders
{
    const Facebook = "facebook";
    const Apple = "apple";
    const LinkedIn = "linkedin";
    const Google = "google";
    const OKTA = 'okta';
    const LFID = 'lfid';

    const ValidProviders = [
        self::Facebook,
        self::LinkedIn,
        self::Apple,
        //self::Google
        self::OKTA,
        self::LFID,
    ];

    /**
     * @param string $provider
     * @return bool
     */
    public static function isSupportedProvider(string $provider): bool
    {
        return in_array($provider, self::ValidProviders);
    }

    /**
     * @return string[]
     */
    public static function buildSupportedProviders(): array
    {
        $res = [];
        $tenant = trim(Request::get('tenant', ''));
        $allowed_3rd_party_providers = self::toList(
            Config::get("tenants.$tenant.allowed_3rd_party_providers", '')
        );

        Log::debug("SocialLoginProviders::buildSupportedProviders", ["tenant" => $tenant, "allowed_3rd_party_providers" => $allowed_3rd_party_providers]);
        foreach (self::ValidProviders as $provider) {
            Log::debug("SocialLoginProviders::buildSupportedProviders", ["tenant" => $tenant, "provider" => $provider]);

            if (!self::isEnabledProvider($provider)) {
                Log::warning("SocialLoginProviders::buildSupportedProviders provider is not enabled.", ["tenant" => $tenant, "provider" => $provider]);
                continue;
            }

            // If no tenant param was provided, any enabled provider is allowed.
            if ($tenant === '') {
                $res[$provider] = ucfirst($provider);
                continue;
            }

            // check if the 3rd party provider has defined some exclusive tenants ...
            $tenants =  self::toList(
                Config::get("services.$provider.tenants", '')
            );

            Log::debug(sprintf("SocialLoginProviders::buildSupportedProviders provider %s is enabled", $provider));
            // 1. check if we have exclusive tenants defined at provider level
            if (count($tenants) > 0 && !in_array($tenant, $tenants)) {
                // tenant is not defined on the exclusive collection of the provider
                Log::warning
                (
                    sprintf
                    (
                        "SocialLoginProviders::buildSupportedProviders provider %s is not enabled for tenant %s",
                        $provider,
                        $tenant
                    ),
                    ["tenants" => $tenants]
                );
                continue;
            }
            // 2. check if the tenant has that provider enabled
            if (!count($tenants) && !in_array($provider, $allowed_3rd_party_providers)) {
                Log::warning
                (
                    sprintf
                    (
                        "SocialLoginProviders::buildSupportedProviders provider %s is not enabled for tenant %s",
                        $provider,
                        $tenant
                    ),
                    ["allowed_3rd_party_providers" => $allowed_3rd_party_providers]
                );
                continue;
            }

            Log::debug(sprintf("SocialLoginProviders::buildSupportedProviders provider %s is added", $provider));
            $res[$provider] = ucfirst($provider);
        }

        return $res;
    }

    private static function toList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), static fn($v) => $v !== ''));
        }
        if (is_string($value)) {
            if ($value === '') return [];
            return array_values(array_filter(array_map('trim', explode(',', $value)), static fn($v) => $v !== ''));
        }
        return [];
    }

    /**
     * @param string $provider
     * @return bool
     */
    public static function isEnabledProvider(string $provider): bool
    {
        return !empty(Config::get("services." . $provider . ".client_id", null)) &&
            !empty(Config::get("services." . $provider . ".client_secret", null));
    }

}