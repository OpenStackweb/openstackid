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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20200306133045
 * @package Database\Migrations
 */
class Version20200306133045 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("users") && !$builder->hasColumn("users","spam_type") ) {
            $builder->table('users', function (Table $table) {
                $table->string('spam_type')->setNotnull(true)->setDefault('None');
            });
        }

        if(!$schema->hasTable("users_spam_estimator_feed")) {
            $builder->create('users_spam_estimator_feed', function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("first_name", 100)->setNotnull(false);
                $table->string("last_name", 100)->setNotnull(false);
                $table->string("email", 255)->setNotnull(false);
                $table->unique("email");
                $table->text("bio")->setNotnull(false);
                $table->string('spam_type')->setNotnull(true)->setDefault('None');
            });
        }

        if(!$schema->hasTable("users_deleted")) {
            $builder->create('users_deleted', function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("first_name", 100)->setNotnull(false);
                $table->string("last_name", 100)->setNotnull(false);
                $table->string("email", 255)->setNotnull(false);
                $table->unique("email");
                $table->bigInteger("performer_id")->setUnsigned(true);
                $table->index("performer_id", "performer_id");
                $table->foreign("users", "performer_id", "id", ["onDelete" => "CASCADE"]);
            });
        }

        if(!$schema->hasTable("users_email_changed")) {
            $builder->create('users_email_changed', function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("former_email", 255)->setNotnull(false);
                $table->string("new_email", 255)->setNotnull(false);
                $table->bigInteger("user_id")->setUnsigned(true);
                $table->index("user_id", "user_id");
                $table->foreign("users", "user_id", "id", ["onDelete" => "CASCADE"]);
                $table->bigInteger("performer_id")->setUnsigned(true);
                $table->index("performer_id", "performer_id");
                $table->foreign("users", "performer_id", "id", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists("users_email_changed");
        $builder->dropIfExists("users_deleted");
        $builder->dropIfExists("users_spam_estimator_feed");
    }
}
