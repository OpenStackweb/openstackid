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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20201105184620
 * @package Database\Migrations
 */
class Version20201105184620 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("users") && !$builder->hasColumn("users","job_title") ) {
            $builder->table('users', function (Table $table) {
                $table->string('job_title')->setNotnull(false)->setLength(254);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("users") && $builder->hasColumn("users","job_title") ) {
            $builder->table('users', function (Table $table) {
                $table->dropColumn('job_title');
            });
        }
    }
}
