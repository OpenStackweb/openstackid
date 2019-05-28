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
use Models\OAuth2\ApiScope;
use models\utils\IBaseRepository;
/**
 * Interface IApiScopeRepository
 * @package OAuth2\Repositories
 */
interface IApiScopeRepository extends IBaseRepository
{
    /**
     * @param array $scopes_names
     * @return ApiScope[]
     */
    public function getByNames(array $scopes_names):array ;

    /**
     * @param string $scope_name
     * @return ApiScope
     */
    public function getFirstByName(string $scope_name):?ApiScope;

    /**
     * @return ApiScope[]
     */
    public function getDefaults():array;

    /**
     * @return ApiScope[]
     */
    public function getActives():array;

    /**
     * @return ApiScope[]
     */
    public function getAssignableByGroups():array;

    /**
     * @param array $scopes_names
     * @return string[]
     */
    public function getFriendlyScopesByName(array $scopes_names):array;

    /**
     * Get all active scopes (system/non system ones)
     * @param bool $system
     * @param bool $assigned_by_groups
     * @return ApiScope[]
     */
    public function getAvailableScopes(bool $system = false, bool $assigned_by_groups = false):array;

}