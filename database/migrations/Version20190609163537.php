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
 * Class Version20190609163537
 * @package Database\Migrations
 */
final class Version20190609163537 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->table('server_configuration', function (Table $table) {
            $table->timestamps();
        });

        $builder->table('openid_trusted_sites', function (Table $table) {
            $table->timestamps();
        });

        $builder->table('openid_associations', function (Table $table) {
            $table->timestamps();
        });

        $builder->table('oauth2_api_endpoint', function (Table $table) {
            $table->boolean("allow_credentials")->setDefault(false);
            $table->integer("rate_limit_decay")->setDefault(0);
        });
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
