<?php namespace App\Jobs;
/*
 * Copyright 2024 OpenStack Foundation
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

/**
 * Class RevokeUserGrantsOnPasswordChange
 * Revokes all OAuth2 grants for a user across all clients after a password change.
 * @package App\Jobs
 */
class RevokeUserGrantsOnPasswordChange extends RevokeUserGrants
{
    public function __construct(User $user)
    {
        parent::__construct($user, null, 'password change');
    }
}
