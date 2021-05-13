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

    const ValidProviders = [
        self::Facebook,
        self::Apple,
        self::LinkedIn,
        self::Google
    ];

    /**
     * @param string $provider
     * @return bool
     */
    public static function isSupportedProvider(string $provider):bool{
        return in_array($provider, self::ValidProviders);
    }

    /**
     * @return string[]
     */
    public static function buildSupportedProviders():array{
        return [
            self::Facebook => "Facebook",
            self::Apple => "Apple",
            self::LinkedIn => "LinkedIn",
            self::Google => "Google",
        ];
    }
}