<?php namespace App\libs\OAuth2\Repositories;
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
use Models\OAuth2\Client;
use models\utils\IBaseRepository;
/**
 * Interface IOAuth2TrailExceptionRepository
 * @package App\libs\OAuth2\Repositories
 */
interface IOAuth2TrailExceptionRepository extends IBaseRepository
{
    /**
     * @param Client $client
     * @param string $type
     * @param int $minutes_without_ex
     * @return int
     */
    public function getCountByIPTypeOfLatestUserExceptions(Client $client, string $type, int $minutes_without_ex):int;
}