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

use AWS\CRT\Log;
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
        if(!filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = @parse_url($url);
        if ($parts == false)
        {
            return null;
        }
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