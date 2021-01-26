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
 * Class Version20200528175450
 * @package Database\Migrations
 */
class Version20200528175450 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("sso_disqus_profile")) {
            $builder->create("sso_disqus_profile", function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("forum_slug")->setNotnull(true);
                $table->string("public_key")->setNotnull(true);
                $table->string("secret_key")->setNotnull(true);
                $table->unique("forum_slug");
            });
        }

        if (!$builder->hasTable("sso_rocket_chat_profile")) {
            $builder->create("sso_rocket_chat_profile", function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("forum_slug")->setNotnull(true);
                $table->string("base_url")->setNotnull(true);
                $table->string("service_name")->setNotnull(true);
                $table->unique("forum_slug");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("sso_disqus_profile");

        $builder->dropIfExists("sso_rocket_chat_profile");
    }
}
