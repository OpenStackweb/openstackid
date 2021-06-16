<?php namespace Database\Migrations;
/**
 * Copyright 2021 OpenStack Foundation
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
 * Class Version20210616123839
 * @package Database\Migrations
 */
class Version20210616123839 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("oauth2_otp")) {
            $builder->create("oauth2_otp", function (Table $table) {
                $table->bigInteger("id", true, false);
                $table->primary("id");
                $table->timestamps();
                $table->string("value")->setLength(50)->setNotnull(true);
                $table->string("connection")->setLength(10)->setNotnull(true);//sms|mail
                $table->string("send")->setLength(10)->setNotnull(true);//code|link
                $table->text("scope")->setNotnull(false);
                $table->string("email")->setLength(50)->setNotnull(false);
                $table->string("phone_number")->setLength(50)->setNotnull(false);
                $table->string("nonce")->setLength(50)->setNotnull(false);
                $table->integer("redeemed_attempts")->setNotnull(true)->setDefault(0);
                $table->string("redeemed_from_ip")->setNotnull(false);
                $table->string("redirect_url")->setLength(255)->setNotnull(false);
                $table->integer('length')->setNotnull(true)->setDefault(6);
                // seconds
                $table->integer('lifetime')->setNotnull(true)->setDefault(600);
                $table->dateTime('redeemed_at')->setNotnull(false);
                // FK Optional
                $table->unsignedBigInteger("oauth2_client_id", false)->setNotnull(false)->setDefault('NULL');
                $table->index("oauth2_client_id", "oauth2_client_id");
                $table->foreign("oauth2_client", "oauth2_client_id", "id", ["onDelete" => "CASCADE"]);
                $table->unique(["oauth2_client_id", "value"]);

            });
        }

        if ($builder->hasTable("oauth2_client") && !$builder->hasColumn("oauth2_client","otp_enabled")) {
            $builder->table('oauth2_client', function (Table $table) {
                //
                $table->boolean('otp_enabled')->setNotnull(false)->setDefault(0);
                // characters
                $table->integer('otp_length')->setNotnull(false)->setDefault(6);
                // seconds
                $table->integer('otp_lifetime')->setNotnull(false)->setDefault(120);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("oauth2_otp")) {
            $builder->drop("oauth2_otp");
        }

        if ($builder->hasTable("oauth2_client") && $builder->hasColumn("oauth2_client","otp_enabled")) {
            $builder->table('oauth2_client', function (Table $table) {
                //
                $table->dropColumn('otp_enabled');
                // characters
                $table->dropColumn('otp_length');
                // seconds
                $table->dropColumn('otp_lifetime');
            });
        }
    }
}
