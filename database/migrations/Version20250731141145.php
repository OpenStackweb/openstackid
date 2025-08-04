<?php namespace Database\Migrations;
/**
 * Copyright 2025 OpenStack Foundation
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
use App\libs\OAuth2\IGroupScopes;
use Auth\Group;
use Database\Seeders\SeedUtils;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\ORM\Facades\EntityManager;
/**
 * Class Version20250731141145
 * @package Database\Migrations
 */
class Version20250731141145 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $group = EntityManager::getRepository(Group::class)->findOneBy(['slug' => IGroupSlugs::SponsorServicesGroup]);
        if(is_null($group)){
            $group = new Group();
            $group->setName('Sponsors Services');
            $group->setSlug(IGroupSlugs::SponsorServicesGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['slug' => IGroupSlugs::SponsorUsersGroup]);
        if(is_null($group)){
            $group = new Group();
            $group->setName('Sponsors Users');
            $group->setSlug(IGroupSlugs::SponsorUsersGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        $group = EntityManager::getRepository(Group::class)->findOneBy(['slug' => IGroupSlugs::SponsorExternalUsersGroup]);
        if(is_null($group)){
            $group = new Group();
            $group->setName('Sponsors External Users');
            $group->setSlug(IGroupSlugs::SponsorExternalUsersGroup);
            $group->setDefault(false);
            $group->setActive(true);
            EntityManager::persist($group);
            EntityManager::flush();
        }

        if(!SeedUtils::seedApi("groups", "Groups Info API")) return;

        SeedUtils::seedScopes([
            [
                'name'               => IGroupScopes::ReadAll,
                'short_description'  => 'Allows access to Groups info.',
                'description'        => 'Allows access to Groups info.',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ],
            [
                'name'               => IGroupScopes::Write,
                'short_description'  => 'Allows access to write Groups info.',
                'description'        => 'Allows access to write Groups info.',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ],
        ], 'groups');

        SeedUtils::seedApiEndpoints('groups', [
            [
                'name' => 'get-groups',
                'active' => true,
                'route' => '/api/v1/groups',
                'http_method' => 'GET',
                'scopes' => [
                    \App\libs\OAuth2\IGroupScopes::ReadAll
                ],
            ],
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
