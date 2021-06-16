<?php namespace App\libs\OAuth2\Repositories;
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

use Models\OAuth2\Client;
use Models\OAuth2\OAuth2OTP;
use models\utils\IBaseRepository;
/**
 * Interface IOAuth2OTPRepository
 * @package App\libs\OAuth2\Repositories
 */
interface IOAuth2OTPRepository extends IBaseRepository
{
    /**
     * @param string $value
     * @return OAuth2OTP|null
     */
    public function getByValue(string $value):?OAuth2OTP;

    /**
     * @param string $connection
     * @param string $user_name
     * @param Client|null $client
     * @return OAuth2OTP|null
     */
    public function getByConnectionAndUserNameNotRedeemed
    (
        string $connection,
        string $user_name,
        ?Client $client
    ):?OAuth2OTP;

    /**
     * @param string $user_name
     * @param Client|null $client
     * @return OAuth2OTP[]
     */
    public function getByUserNameNotRedeemed
    (
        string $user_name,
        ?Client $client = null
    );
}