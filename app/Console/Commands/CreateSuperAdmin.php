<?php namespace App\Console\Commands;
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

use Auth\Group;
use Auth\User;
use Illuminate\Console\Command;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class CreateSuperAdmin
 * @package App\Console\Commands
 */
class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'idp:create-super-admin {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Super Admin User';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $email = trim($this->argument('email'));
        $password = trim($this->argument('password'));

        $user = EntityManager::getRepository(User::class)->findOneBy(['email' => $email]);
        if(is_null($user)) {
            $user = new User();
            $user->setEmail($email);
            $user->verifyEmail();
            $user->setPassword($password);
            $user->setFirstName($email);
            $user->setLastName($email);
            $user->setIdentifier($email);
            EntityManager::persist($user);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'super admins']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('super admins');
            $group->setSlug('super-admins');
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        try {
            $user->addToGroup($group);
        }
        catch (\Exception $ex){
            // already in group
        }
        EntityManager::persist($user);
        EntityManager::flush();
    }
}
