<?php namespace Auth\Repositories;
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
use models\utils\IBaseRepository;
/**
 * Interface IUserRepository
 * @package Auth\Repositories
 */
interface IUserRepository extends IBaseRepository
{

    /**
     * @param string $token
     * @return User
     */
    public function getByToken(string $token):?User;

    /**
     * @param string $term
     * @return User
     */
    public function getByEmailOrName(string $term):?User;

    /**
     * @param string $user_identifier
     * @return User
     */
    public function getByIdentifier($user_identifier):?User;

    /**
     * @param string $token
     * @return User|null
     */
    public function getByVerificationEmailToken(string $token):?User;
} 