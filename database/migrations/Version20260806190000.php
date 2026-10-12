<?php namespace Database\Migrations;
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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20260806190000
 * @package Database\Migrations
 */
class Version20260806190000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("facebook_deletion_requests")) {
            $builder->create("facebook_deletion_requests", function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->string("provider")->setNotnull(true);
                $table->string("external_id")->setNotnull(true);
                $table->string("confirmation_code")->setNotnull(true);
                $table->string("status")->setNotnull(true);
                $table->bigInteger("user_id")->setUnsigned(true)->setNotnull(false);
                $table->index("user_id", "user_id");
                $table->foreign("users", "user_id", "id", ["onDelete" => "SET NULL"]);
                $table->unique(["provider", "external_id"]);
                $table->unique("confirmation_code");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("facebook_deletion_requests");
    }
}
