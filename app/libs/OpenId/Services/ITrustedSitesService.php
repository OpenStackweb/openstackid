<?php namespace OpenId\Services;
/**
 * Copyright 2016 OpenStack Foundation
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
use Models\OpenId\OpenIdTrustedSite;
/**
 * Interface ITrustedSitesService
 * @package OpenId\Services
 */
interface ITrustedSitesService
{
    /**
     * @param User $user
     * @param string $realm
     * @param string $policy
     * @param array $data
     * @return OpenIdTrustedSite
     */
    public function addTrustedSite(User $user, string $realm, string $policy, $data = []):OpenIdTrustedSite;

    /**
     * @param $id
     * @return bool
     */
    public function delete(int $id):void;

    /**
     * @param User $user
     * @param string $realm
     * @param array $data
     * @return array
     */
    public function getTrustedSites(User $user, string $realm, $data = []):array;

}