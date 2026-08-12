<?php namespace App\libs\Utils;
/**
 * Copyright 2020 OpenStack Foundation
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

use Illuminate\Support\Facades\Log;
use URL\Normalizer;
/**
 * Class URLUtils
 * @package App\libs\Utils
 */
final class URLUtils
{
    /**
     * @param string $url
     * @return string|null
     */
    public static function normalizeUrl(string $url):?string{
        return (new Normalizer($url))->normalize();
    }

    /**
     * @param string $url
     * @param bool $usePort
     * @return string|null
     */
    public static function canonicalUrl(string $url, bool $usePort = true):?string{
        $parts = @parse_url($url);
        if ($parts == false || !isset($parts['scheme']))
        {
            return null;
        }
        if (!isset($parts['host'])) {
            // RFC 8252 SS7.1: private-use scheme redirect URIs may omit the authority entirely -
            // "com.example.app:/oauth2redirect/example-provider" is the RFC's own example form.
            // FILTER_VALIDATE_URL rejects that shape, so it is canonicalized here from parse_url parts:
            // scheme + rooted path (query/fragment dropped, path lowercased, same as the authority form).
            // Opaque URIs (mailto:foo@bar - no authority AND no rooted path) keep returning null: there
            // is no location to match a redirect against.
            if (!isset($parts['path']) || !str_starts_with($parts['path'], '/') || isset($parts['user']) || isset($parts['port'])) {
                return null;
            }
            return rtrim($parts['scheme'].':'.strtolower($parts['path']), '/');
        }
        if(!filter_var($url, FILTER_VALIDATE_URL)) return null;
        $canonical_url = $parts['scheme'].'://'.strtolower($parts['host']);
        if(isset($parts['port']) && $usePort) {
            $canonical_url .= ':'.strtolower($parts['port']);
        }
        if(isset($parts['path'])) {
            $canonical_url .= strtolower($parts['path']);
        }
        return rtrim($canonical_url, '/');
    }

    /**
     * The single canonicalization pipeline every runtime URI-matching gate feeds BOTH sides through:
     * canonicalUrl() (validity, scheme://host[:port]/path or RFC 8252 SS7.1 authority-less scheme:/path,
     * query/fragment dropped, host/path lowercased) followed by normalizeUrl() (RFC 3986 normalization -
     * scheme lowercased, default ports removed). Returns null when the URI cannot be canonicalized;
     * callers treat that as "cannot match anything".
     *
     * @param string $uri
     * @param bool $usePort
     * @return string|null
     */
    public static function canonicalizeForMatch(string $uri, bool $usePort = true):?string{
        $canonical = self::canonicalUrl($uri, $usePort);
        if(empty($canonical)) return null;
        $normalized = self::normalizeUrl($canonical);
        return empty($normalized) ? null : $normalized;
    }

    /**
     * The single registered-URI-list matcher behind Client::isUriAllowed()/isPostLogoutUriAllowed()/
     * isOriginAllowed(): exact match of ANY of the requested canonical forms against EACH item of the
     * stored comma-separated registration list, the registered side canonicalized through the same
     * canonicalizeForMatch() pipeline the caller used for the requested side. Per-field matching
     * differences (loopback port-agnostic redirects, the origin with/without-port dual form) are
     * expressed by the CALLERS via $requested_canonicals/$registered_use_port - the matching
     * algorithm itself exists only here.
     *
     * @param string[] $requested_canonicals already-canonicalized acceptable forms of the requested URI
     * @param string|null $registered_csv the stored comma-separated registration list
     * @param bool $registered_use_port whether registered items keep their explicit port when canonicalized
     * @param string $log_context caller tag for debug traceability
     * @return bool
     */
    public static function anyCanonicalMatchesList(array $requested_canonicals, ?string $registered_csv, bool $registered_use_port, string $log_context):bool{
        if(empty($registered_csv)) return false;
        foreach(explode(',', $registered_csv) as $registered_uri){
            $registered_uri = trim($registered_uri);
            if(empty($registered_uri)) continue;

            $canonical_registered_uri = self::canonicalizeForMatch($registered_uri, $registered_use_port);
            if(is_null($canonical_registered_uri)) continue;

            Log::debug(sprintf("%s comparing requested (%s) against registered %s", $log_context, implode('|', $requested_canonicals), $canonical_registered_uri));
            if(in_array($canonical_registered_uri, $requested_canonicals, true))
                return true;
        }
        return false;
    }

    /**
     * @param string $uri
     * @return bool
     */
    public static function isHTTPS(string $uri):bool{
        if(!filter_var($uri, FILTER_VALIDATE_URL)) return false;
        $parts = @parse_url($uri);
        if ($parts == false)
        {
            return false;
        }
        return $parts['scheme'] === 'https';
    }

    /**
     * Get all possible sub-domains for a given url
     * @param string $url
     * @return array
     */
    public static function getSubDomains(string $url):array
    {
        $res    = [];
        $url    = strtolower($url);
        $scheme = self::getScheme($url);
        //add entire url as first domain
        $res[] = $url;

        $ends_with_slash = substr($url, -1) == '/';
        $url             = parse_url($url);
        $authority       = $url['host'];
        $components      = explode('.', $authority);
        $len             = count($components);

        for ($i = 0; $i < $len; $i++) {
            if ($components[$i] == '*') continue;
            $str = '';
            for ($j = $i; $j < $len; $j++)
                $str .= $components[$j] . '.';
            $str = trim($str, '.');
            $str = $ends_with_slash ? $str . '/' : $str;
            $newSubDomain =  $scheme . '*.' . $str;
            if(!in_array($newSubDomain, $res))
                $res[] = $newSubDomain;
        }
        // remove generic domain
        if(count($res) > 0)
        {
            array_pop($res);
        }
        return $res;
    }

    /**
     * @param string $url
     * @return string|null
     */
    public static function getScheme(string $url):?string
    {
        $url    = parse_url(strtolower($url));
        if(!$url) return null;
        if (isset($url['scheme']) && !empty($url['scheme'])) {
            return $url['scheme'] . '://';
        }
        return null;
    }

}