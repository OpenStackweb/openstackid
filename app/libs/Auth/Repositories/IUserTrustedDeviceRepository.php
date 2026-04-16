<?php namespace Auth\Repositories;
/**
 * Copyright 2026 OpenStack Foundation
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
use App\libs\Auth\Models\UserTrustedDevice;
use Auth\User;
use models\utils\IBaseRepository;

interface IUserTrustedDeviceRepository extends IBaseRepository
{
    /**
     * Look up an active (non-revoked) trusted device for a user by its hashed identifier.
     */
    public function getActiveByUserAndIdentifier(User $user, string $deviceIdentifier): ?UserTrustedDevice;

    /**
     * @return UserTrustedDevice[]
     */
    public function getActiveByUser(User $user): array;
}
