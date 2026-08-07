<?php namespace App\libs\Auth;
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

/**
 * Class FacebookSignedRequestParser
 * Parses and verifies Facebook's `signed_request` payload used by the
 * Data Deletion Request Callback.
 * @see https://developers.facebook.com/documentation/development/create-an-app/app-dashboard/data-deletion-callback
 * @package App\libs\Auth
 */
final class FacebookSignedRequestParser
{
    /**
     * @param string $signed_request
     * @param string $secret
     * @return array|null
     */
    public static function parse(string $signed_request, string $secret): ?array
    {
        $parts = explode('.', $signed_request, 2);
        if (count($parts) !== 2) return null;

        [$encoded_sig, $payload] = $parts;

        $sig = self::base64UrlDecode($encoded_sig);
        $data = json_decode(self::base64UrlDecode($payload), true);

        if (!is_array($data)) return null;
        if (strtoupper($data['algorithm'] ?? '') !== 'HMAC-SHA256') return null;
        if (empty($data['user_id'])) return null;

        $expected_sig = hash_hmac('sha256', $payload, $secret, true);

        if (!hash_equals($expected_sig, $sig)) return null;

        return $data;
    }

    /**
     * @param string $input
     * @return string
     */
    private static function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
