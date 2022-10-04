<?php namespace OAuth2\Repositories;
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
use Models\OAuth2\Client;
use models\utils\IBaseRepository;
/**
 * Interface IClientRepository
 * @package OAuth2\Repositories
 */
interface IClientRepository extends IBaseRepository
{
    /**
     * @param string $app_name
     * @return Client|null
     */
    public function getByApplicationName(string $app_name):?Client;

    /**
     * @param string $client_id
     * @return Client|null
     */
    public function getClientById(string $client_id):?Client;

    /**
     * @param string $client_id
     * @return Client|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getClientByIdCacheable(string $client_id):?Client;

    /**
     * @param int $id
     * @return Client|null
     */
    public function getClientByIdentifier(int $id):?Client;

    /**
     * @param string $origin
     * @return Client|null
     */
    public function getByOrigin(string $origin):?Client;

    /**
     * @param int $id
     * @param string $custom_scheme
     * @return bool
     */
    public function hasCustomSchemeRegisteredForRedirectUrisOnAnotherClientThan(int $id, string $custom_scheme):bool;
}