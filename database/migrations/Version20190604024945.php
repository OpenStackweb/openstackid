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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20190604024945
 * @package Database\Migrations
 */
class Version20190604024945 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->table('users', function (Table $table) {
            $table->string("first_name", 100)->setNotnull(false);
            $table->string("last_name", 100)->setNotnull(false);
            $table->timestamp("birthday")->setNotnull(false);
            $table->string("email", 255)->setNotnull(false);
            $table->unique("email");
            $table->string("address1", 255)->setNotnull(false);
            $table->string("address2", 255)->setNotnull(false);
            $table->string("state", 100)->setNotnull(false);
            $table->string("city", 100)->setNotnull(false);
            $table->string("post_code", 64)->setNotnull(false);
            $table->string("country_iso_code", 2)->setNotnull(false);
            $table->string("second_email", 255)->setNotnull(false);
            $table->string("third_email", 255)->setNotnull(false);
            $table->string("gender", 100)->setNotnull(false);
            $table->text("statement_of_interest")->setNotnull(false);
            $table->text("bio")->setNotnull(false);
            $table->string("language", 10)->setNotnull(false);
            $table->text("irc")->setNotnull(false);
            $table->text("linked_in_profile")->setNotnull(false);
            $table->text("github_user", 100)->setNotnull(false);
            $table->text("wechat_user", 100)->setNotnull(false);
            $table->string("password", 255)->setNotnull(false);
            $table->string("password_enc", 100)->setNotnull(false);
            $table->string("password_salt", 255)->setNotnull(false);
            $table->boolean("email_verified")->setDefault(0);
            $table->text("email_verified_token_hash")->setNotnull(false);
            $table->timestamp("email_verified_date")->setNotnull(false);
            $table->dropColumn("last_login_date");
            $table->timestamp("last_login_date")->setNotnull(false);
        });

        $builder->create('groups', function (Table $table) {
            $table->increments('id');
            $table->timestamps();
            $table->text("name");
            $table->boolean("active")->setDefault(false);
            $table->boolean("is_default")->setDefault(false);
            $table->string("slug", 255);
        });

        $builder->create('user_groups', function (Table $table) {
            $table->increments('id');
            $table->bigInteger("user_id")->setUnsigned(true);
            $table->index("user_id", "user_id");
            $table->foreign("users", "user_id", "id");
            $table->integer("group_id")->setUnsigned(true);
            $table->index("group_id", "group_id");
            $table->foreign("groups", "group_id", "id");
        });

        $builder->create('organizations', function (Table $table) {
            $table->increments('id');
            $table->timestamps();
            $table->text("name");
        });

        $builder->create('affiliations', function (Table $table) {
            $table->increments('id');
            $table->timestamps();
            $table->timestamp('start_date');
            $table->timestamp('end_date')->setNotnull(false);
            $table->text("job_title");
            $table->text("role")->setNotnull(false);
            $table->boolean("is_current")->setDefault(0);
            $table->bigInteger("user_id")->setUnsigned(true);
            $table->index("user_id", "user_id");
            $table->foreign("users", "user_id", "id");
            $table->integer("organization_id")->setUnsigned(true);
            $table->index("organization_id", "organization_id");
            $table->foreign("organizations", "organization_id", "id");
        });
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $schema->dropTable("user_groups");
        $schema->dropTable("groups");
    }
}
