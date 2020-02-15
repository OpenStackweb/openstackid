<?php namespace App\Http\Utils;
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
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Log;
/**
 * Class CookieSameSitePolicy
 * https://www.chromium.org/updates/same-site/incompatible-clients
 * @package App\Http\Utils
 */
final class CookieSameSitePolicy
{
    /**
     * @return bool
     */
    public static function isSameSiteNoneIncompatible():bool {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if(empty($user_agent)) return true;
        Log::debug(sprintf("CookieSameSitePolicy::isSameSiteNoneIncompatible user_agent %s", $user_agent));
        return self::hasWebKitSameSiteBug($user_agent) ||
            self::dropsUnrecognizedSameSiteCookies($user_agent);
    }

    /**
     * @param string $user_agent
     * @return bool
     */
    public static function hasWebKitSameSiteBug(string $user_agent):bool {
        return self::isIosVersion($user_agent,12) ||
           (self::isMacOSXVersion( $user_agent,10, 14) &&
            (self::isSafari($user_agent) || self::isMacEmbeddedBrowser($user_agent)));
    }

    /**
     * @param string $user_agent
     * @param int $major
     * @return bool
     */
    public static function isIosVersion(string $user_agent, int $major):bool{

        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        $os          = $parser->platform();
        $os_version  = $parser->version($os);
        $os_version  = explode( '_', $os_version);
        return $os=="iOS" &&  count($os_version) > 0 && intval($os_version[0]) == $major;
    }

    /**
     * @param string $user_agent
     * @param int $major
     * @param int $minor
     * @return bool
     */
    public static function isMacOSXVersion(string $user_agent, int $major, int $minor):bool{

        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        $os          = $parser->platform();
        $os_version  = $parser->version($os);
        $os_version  = explode( '_', $os_version);
        return $os == "OS X" && count($os_version) > 1 && intval($os_version[0]) == $major && intval($os_version[1]) == $minor;
    }

    /**
     * @param string $user_agent
     * @return bool
     */
    public static function isSafari(string $user_agent):bool{
        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        return $parser->browser() == 'Safari';
    }

    /**
     * @param string $user_agent
     * @return bool
     */
    public static function isMacEmbeddedBrowser(string $user_agent):bool{
        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        return $parser->match("^Mozilla\/[\.\d]+ \(Macintosh;.*Mac OS X [_\d]+\) AppleWebKit\/[\.\d]+ \(KHTML, like Gecko\)$");
    }

    /**
     * @param string $user_agent
     * @return bool
     */
    public static function dropsUnrecognizedSameSiteCookies(string $user_agent):bool {
        if(self::isUcBrowser($user_agent))
            return self::isUcBrowserVersionAtLeast($user_agent, 12, 13,2);
        return self::isChromiumBased($user_agent)
            && self::isChromiumVersionAtLeast($user_agent, 51)
            && !self::isChromiumVersionAtLeast($user_agent, 67);
    }

    /**
     * @param string $user_agent
     * @return bool
     */
    public static function isUcBrowser(string $user_agent):bool {
        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        return $parser->browser() == 'UCBrowser';
    }

    /**
     * @param string $user_agent
     * @param int $major
     * @param int $minor
     * @param int $build
     * @return bool
     */
    public static function isUcBrowserVersionAtLeast(string $user_agent, int $major, int $minor, int $build):bool{
        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        $browser = $parser->browser();
        $browser_version  = $parser->version($browser);
        $browser_version  = explode( '.', $browser_version);
        if(count($browser_version) < 3) return false;
        $major_version = intval($browser_version[0]);
        $minor_version = intval($browser_version[1]);
        $build_version = intval($browser_version[2]);
        if($browser != 'UCBrowser') return false;
        if($major_version != $major)
            return $major_version > $major;
        if($minor_version != $minor)
            return $minor_version > $minor;
        return $build_version >= $build;
    }

    public static function isChromiumBased(string $user_agent):bool {
        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        $browser = $parser->browser();
        return $browser == 'Chrome';
    }

    /**
     * @param string $user_agent
     * @param int $major
     * @return bool
     */
    public static function isChromiumVersionAtLeast(string $user_agent, int $major):bool {
        $parser = new Agent();
        $parser->setUserAgent($user_agent);
        $browser = $parser->browser();
        $browser_version  = $parser->version($browser);
        $browser_version  = explode( '.', $browser_version);
        if(count($browser_version) < 1) return false;
        $major_version = intval($browser_version[0]);
        return $browser == 'Chrome' && $major_version >= $major;
    }
}