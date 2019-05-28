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
use App\Services\IBaseService;
use Auth\User;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
/**
 * Interface IUserService
 * @package OpenId\Services
 */
interface IUserService extends IBaseService
{
    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function updateLastLoginDate(int $user_id):User;

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function updateFailedLoginAttempts(int $user_id):User;

    /**
     * @param int $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function lockUser(int $user_id):User;

    /**
     * @param $user_id
     * @return User
     * @throws EntityNotFoundException
     */
    public function unlockUser(int $user_id):User;

    /**
     * @param int $user_id
     * @param bool $show_pic
     * @param bool $show_full_name
     * @param bool $show_email
     * @param string $identifier
     * @return bool
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function saveProfileInfo($user_id, $show_pic, $show_full_name, $show_email, $identifier);

}