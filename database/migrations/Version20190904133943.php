<?php namespace Database\Migrations;
/**
 * Copyright 2017 OpenStack Foundation
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

use App\libs\Auth\Models\IGroupSlugs;
use Auth\Group;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class Version20190904133943
 * @package Database\Migrations
 */
class Version20190904133943 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'super admins']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('super admins');
            $group->setSlug(IGroupSlugs::SuperAdminGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'oauth2 server admins']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('oauth2 server admins');
            $group->setSlug(IGroupSlugs::OAuth2ServerAdminGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'oauth2 system scope admins']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('oauth2 system scope admins');
            $group->setSlug(IGroupSlugs::OAuth2SystemScopeAdminsGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'openstackid server admins']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('openstackid server admins');
            $group->setSlug(IGroupSlugs::OpenIdServerAdminsGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'raw users']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('raw users');
            $group->setSlug(IGroupSlugs::RawUsersGroup);
            $group->setDefault(true);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
