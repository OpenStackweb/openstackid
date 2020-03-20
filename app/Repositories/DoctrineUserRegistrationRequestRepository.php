<?php namespace App\Repositories;
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
use App\libs\Auth\Repositories\IUserRegistrationRequestRepository;
/**
 * Class DoctrineUserRegistrationRequestRepository
 * @package App\Repositories
 */
final class DoctrineUserRegistrationRequestRepository
    extends ModelDoctrineRepository implements IUserRegistrationRequestRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return UserRegistrationRequest::class;
    }

    /**
     * @param string $hash
     * @return UserRegistrationRequest|null
     */
    public function getByHash(string $hash): ?UserRegistrationRequest
    {
        return $this->findOneBy([
            'hash' => $hash
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getByEmail(string $email): ?UserRegistrationRequest
    {
        return $this->findOneBy([
            'email' => strtolower(trim($email))
        ]);
    }
}