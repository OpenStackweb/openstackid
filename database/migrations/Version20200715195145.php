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
 * Class Version20200715195145
 * @package Database\Migrations
 */
class Version20200715195145 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("sso_stream_chat_profile")) {
            $builder->create("sso_stream_chat_profile", function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("forum_slug")->setNotnull(true);
                $table->string("api_key")->setNotnull(true);
                $table->string("api_secret")->setNotnull(true);
                $table->unique("forum_slug");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("sso_stream_chat_profile");
    }
}
