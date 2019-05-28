<?php namespace App\Models\OpenId\Factories;
/**
 * Copyright 2019 OpenStack Foundation
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
use Models\OpenId\OpenIdTrustedSite;
/**
 * Class OpenIdTrustedSiteFactory
 * @package App\Models\OpenId\Factories
 */
final class OpenIdTrustedSiteFactory
{
    /**
     * @param array $payload
     * @return OpenIdTrustedSite
     */
    public static function build(array $payload):OpenIdTrustedSite
    {
        return self::populate(new OpenIdTrustedSite, $payload);
    }

    /**
     * @param OpenIdTrustedSite $site
     * @param array $payload
     * @return OpenIdTrustedSite
     */
    public static function populate(OpenIdTrustedSite $site, array $payload):OpenIdTrustedSite
    {
        if(isset($payload['realm']))
            $site->setRealm(trim($payload['realm']));
        if(isset($payload['policy']))
            $site->setPolicy(trim($payload['policy']));
        if(isset($payload['data']))
            $site->setData(trim($payload['data']));
        return $site;
    }
}