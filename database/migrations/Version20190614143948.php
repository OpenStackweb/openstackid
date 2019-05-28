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
 * Class Version20190614143948
 * @package Database\Migrations
 */
class Version20190614143948 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->create('user_password_reset_request', function (Table $table) {
            $table->increments('id');
            $table->timestamps();
            $table->timestamp("redeem_at")->setNotnull(false);
            $table->text('hash');
            $table->integer('lifetime');
            $table->bigInteger("owner_id")->setUnsigned(true);
            $table->index("owner_id", "owner_id");
            $table->foreign("users", "owner_id", "id");
        });
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);
        $builder->drop('user_password_reset_request');
    }
}
