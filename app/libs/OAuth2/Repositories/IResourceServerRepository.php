<?php namespace OAuth2\Repositories;
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
use Models\OAuth2\ResourceServer;
use models\utils\IBaseRepository;
/**
 * Interface IResourceServerRepository
 * @package OAuth2\Repositories
 */
interface IResourceServerRepository extends IBaseRepository
{
    /**
     * @param string|array $host
     * @return ResourceServer
     */
    public function getByHost(string $host):?ResourceServer;

    /**
     * @param string $ip
     * @return ResourceServer
     */
    public function getByIp(string $ip):?ResourceServer;

    /**
     * @param string $name
     * @return ResourceServer
     */
    public function getByFriendlyName(string $name):?ResourceServer;

    /**
     * @param array $audience
     * @param string $ip
     * @return ResourceServer
     */
    public function getByAudienceAndIpAndActive(array $audience, string $ip):?ResourceServer;

}