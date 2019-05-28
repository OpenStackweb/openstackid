<?php namespace OAuth2\Services;
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
use Models\OAuth2\Client;
use Models\OAuth2\UserConsent;
use models\exceptions\EntityNotFoundException;
/**
 * Interface IUserConsentService
 * @package OAuth2\Services
 */
interface IUserConsentService
{
    /**
     * @param User $user
     * @param Client $client
     * @param string $scopes
     * @return UserConsent
     * @throws EntityNotFoundException
     */
    public function addUserConsent(User $user, Client $client, string $scopes):UserConsent;
} 