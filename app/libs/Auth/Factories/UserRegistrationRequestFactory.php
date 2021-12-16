<?php namespace App\libs\Auth\Factories;
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
use App\libs\Auth\Models\UserRegistrationRequest;
/**
 * Class UserRegistrationRequestFactory
 * @package App\libs\Auth\Factories
 */
final class UserRegistrationRequestFactory
{
    /**
     * @param array $payload
     * @return UserRegistrationRequest
     */
    public static function build(array $payload):UserRegistrationRequest{
        $request =  self::populate(new UserRegistrationRequest, $payload);
        return $request;
    }

    /**
     * @param UserRegistrationRequest $request
     * @param array $payload
     * @return UserRegistrationRequest
     */
    public static function populate(UserRegistrationRequest $request, array $payload):UserRegistrationRequest{
        if(isset($payload['email']))
            $request->setEmail(trim($payload['email']));

        if(isset($payload['first_name']))
            $request->setFirstName(trim($payload['first_name']));

        if(isset($payload['last_name']))
            $request->setLastName(trim($payload['last_name']));

        if(isset($payload['company']))
            $request->setCompany(trim($payload['company']));

        if(isset($payload['country']))
            $request->setCountryIsoCode(trim($payload['country']));

        return $request;
    }
}