<?php
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

use App\libs\Auth\Models\IGroupSlugs;

return [
    /*
    |--------------------------------------------------------------------------
    | Enforced Groups
    |--------------------------------------------------------------------------
    |
    | Users that belong to any of these groups are required to complete 2FA
    | regardless of the value of their `two_factor_enabled` flag.
    |
    */
    'enforced_groups' => [
        IGroupSlugs::SuperAdminGroup,
        IGroupSlugs::AdminGroup,
        IGroupSlugs::OAuth2ServerAdminGroup,
        IGroupSlugs::OpenIdServerAdminsGroup,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Trust
    |--------------------------------------------------------------------------
    */
    'device_trust_lifetime_days' => env('DEVICE_TRUST_LIFETIME_DAYS', 30),
    'cookie_name' => env('DEVICE_TRUST_COOKIE_NAME', 'device_trust_token'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Counters live in the cache (NOT the session) so they survive session
    | cleanup and keep an independent, fixed TTL window.
    |
    | verify/recovery: max_attempts failed attempts per window_seconds.
    | resend:          max_otp_requests requests per otp_window_minutes.
    |
    */
    'rate_limit' => [
        'max_attempts'       => env('TWO_FACTOR_MAX_ATTEMPTS', 3),
        'window_seconds'     => env('TWO_FACTOR_RATE_WINDOW_SECONDS', 900),
        'max_otp_requests'   => env('TWO_FACTOR_MAX_OTP_REQUESTS', 5),
        'otp_window_minutes' => env('TWO_FACTOR_OTP_WINDOW_MINUTES', 15),
    ],
];
