<?php namespace Database\Migrations;
/**
 * Copyright 2020 OpenStack Foundation
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

class Version20200910212216 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'chat qa']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('chat qa');
            $group->setSlug(IGroupSlugs::ChatQAGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['name' => 'chat help']);
        if(is_null($group)){
            $group = new Group();
            $group->setName('chat help');
            $group->setSlug(IGroupSlugs::ChatHelpGroup);
            $group->setDefault(false);
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
