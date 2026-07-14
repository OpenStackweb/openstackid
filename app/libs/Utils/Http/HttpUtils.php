<?php namespace Utils\Http;
/**
 * Copyright 2015 OpenStack Foundation
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
 * Class HttpUtils
 * @package Utils\Http
 */
final class HttpUtils
{
    /**
     * Schemes native clients may NOT register in redirect_uris / allowed_origins / post_logout_redirect_uris,
     * even though they are otherwise allowed to register arbitrary custom app schemes there. https is always
     * allowed (checked separately); plain http is handled separately too (see isDisallowedNativeUriScheme -
     * RFC 8252 loopback redirection is a carve-out). Every scheme below, once handed to an OS/browser as a
     * live redirect target, can trigger an unintended action (script execution, app launch, install prompt,
     * local file/content access). Single source of truth for the write-time validator
     * (ClientService::assertNativeCustomSchemesAllowed) and the runtime allow-gates (Client::isUriAllowed,
     * Client::isPostLogoutUriAllowed).
     */
    public const array DISALLOWED_NATIVE_URI_SCHEMES = [
        'javascript', 'data', 'vbscript', 'intent', 'file', 'ftp', 'blob', 'about', 'mailto', 'tel',
        'itms-services', 'market', 'sms', 'content', 'chrome-extension', 'filesystem', 'view-source',
        'ws', 'wss', 'googlechrome', 'applewebdata',
    ];

    /**
     * Loopback hosts exempted from the "plain http is disallowed" rule (RFC 8252 SS7.3): a native app
     * receiving its own redirect on 127.0.0.1/::1/localhost never sends the request over the network, so
     * there is no TLS downgrade to protect against.
     */
    public const array NATIVE_LOOPBACK_HOSTS = ['127.0.0.1', '::1', '[::1]', 'localhost'];

    /**
     * @param string $schema
     * @param string|null $host present when validating a full URI (e.g. redirect_uris); enables the
     *                          RFC 8252 http-loopback carve-out. Omit when only the scheme is known.
     * @return bool
     */
    public static function isDisallowedNativeUriScheme(string $schema, ?string $host = null): bool
    {
        $schema = strtolower($schema);
        if ($schema === 'http') {
            return !in_array(strtolower((string)$host), self::NATIVE_LOOPBACK_HOSTS);
        }
        return in_array($schema, self::DISALLOWED_NATIVE_URI_SCHEMES);
    }

    /**
     * @param string $schema
     * @return bool
     */
    public static function isCustomSchema($schema)
    {
        return !(self::isHttpSchema($schema) || strtolower($schema) === 'ftp' || strtolower($schema) === 'file');
    }

    /**
     * @param string $schema
     * @return bool
     */
    public static function isHttpSchema($schema)
    {
        return (strtolower($schema) === 'http' || self::isHttpsSchema($schema));
    }

    /**
     * @param string $schema
     * @return bool
     */
    public static function isHttpsSchema($schema){
        return (strtolower($schema) === 'https');
    }
}