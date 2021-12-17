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
use utils\DoctrineCaseFilterMapping;
use utils\DoctrineSwitchFilterMapping;
/**
 * Class DoctrineUserRegistrationRequestRepository
 * @package App\Repositories
 */
final class DoctrineUserRegistrationRequestRepository
    extends ModelDoctrineRepository implements IUserRegistrationRequestRepository
{

    /**
     * @return array
     */
    protected function getOrderMappings(): array
    {
        return [
            'email' => 'e.email',
            'id' => 'e.id'
        ];
    }

    /**
     * @return array
     */
    protected function getFilterMappings(): array
    {
        return [
            'first_name'  => 'e.first_name:json_string',
            'last_name'   => 'e.last_name:json_string',
            'email'       => 'e.email:json_string',
            'is_redeemed' =>  new DoctrineSwitchFilterMapping([
                'true' => new DoctrineCaseFilterMapping(
                    'true',
                    "e.redeem_at is not null"
                ),
                'false' => new DoctrineCaseFilterMapping(
                    'false',
                    "e.redeem_at is null"
                ),
            ]),
        ];
    }

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