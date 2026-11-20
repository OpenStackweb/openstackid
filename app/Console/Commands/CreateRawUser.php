<?php namespace App\Console\Commands;
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

use Auth\User;
use Illuminate\Console\Command;
use LaravelDoctrine\ORM\Facades\EntityManager;

/**
 * Class CreateRawUser
 *
 * Creates a plain, verified user with no group memberships.
 * Because this user belongs to none of the groups listed in
 * two_factor.enforced_groups, MFA is never required — making it
 * safe to use in automated E2E tests that must complete the full
 * login → redirect flow without a 2FA challenge.
 *
 * @package App\Console\Commands
 */
class CreateRawUser extends Command
{
    protected $signature = 'idp:create-raw-user {email} {password}';

    protected $description = 'Create a plain verified user with no group memberships (useful for E2E tests)';

    public function handle()
    {
        $email    = trim($this->argument('email'));
        $password = trim($this->argument('password'));

        $user = EntityManager::getRepository(User::class)->findOneBy(['email' => $email]);
        if (is_null($user)) {
            $user = new User();
            $user->setEmail($email);
            $user->verifyEmail();
            $user->setPassword($password);
            $user->setFirstName($email);
            $user->setLastName($email);
            $user->setIdentifier($email);
            EntityManager::persist($user);
            EntityManager::flush();
            $this->info("Created user: {$email}");
        } else {
            $this->info("User already exists: {$email}");
        }
    }
}
