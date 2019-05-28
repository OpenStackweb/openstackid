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
use App\libs\Auth\Repositories\IUserPasswordResetRequestRepository;
use Auth\UserPasswordResetRequest;
/**
 * Class DoctrineUserPasswordResetRequestRepository
 * @package App\Repositories
 */
final class DoctrineUserPasswordResetRequestRepository
    extends ModelDoctrineRepository implements IUserPasswordResetRequestRepository
{

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return UserPasswordResetRequest::class;
    }

    /**
     * @param string $token
     * @return UserPasswordResetRequest|null
     */
    public function getByToken(string $token): ?UserPasswordResetRequest
    {
       return $this->findOneBy([
           'hash' => UserPasswordResetRequest::hash($token)
       ]);
    }
}