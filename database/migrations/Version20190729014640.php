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
 * Class Version20190729014640
 * @package Database\Migrations
 */
final class Version20190729014640 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {

        $builder = new Builder($schema);

        if(!$builder->hasTable("user_registration_requests")){
            $builder->create('user_registration_requests', function (Table $table) {
                $table->increments('id');
                $table->timestamps();
                $table->timestamp("redeem_at")->setNotnull(false);
                $table->text('hash');
                $table->string("first_name", 100)->setNotnull(false);
                $table->string("last_name", 100)->setNotnull(false);
                $table->string("email", 255)->setNotnull(false);
                $table->string("country_iso_code", 2)->setNotnull(false);
                // user associated after creation
                $table->bigInteger("user_id")->setUnsigned(true)->setNotnull(false);
                $table->index("user_id", "user_id");
                $table->foreign("users", "user_id", "id", ["onDelete" => "CASCADE"]);
                // origination oauth2 client
                $table->bigInteger("client_id")->setUnsigned(true)->setNotnull(false);
                $table->index("client_id", "client_id");
                $table->foreign("oauth2_client", "client_id", "id", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $schema->dropTable("user_registration_requests");
    }
}
