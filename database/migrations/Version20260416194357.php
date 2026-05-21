<?php
namespace Database\Migrations;
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
 * Class Version20260416194357
 * @package Database\Migrations
 *
 * Phase I 2FA foundation: adds two_factor_* columns to users and creates
 * user_trusted_devices, two_factor_audit_log, user_recovery_codes tables.
 */
final class Version20260416194357 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        // 1) Add 2FA columns to users
        if ($schema->hasTable("users") && !$builder->hasColumn("users", "two_factor_enabled")) {
            $builder->table('users', function (Table $table) {
                $table->boolean('two_factor_enabled')->setNotnull(true)->setDefault(false);
                $table->string('two_factor_method', 32)->setNotnull(true)->setDefault('email_otp');
                $table->dateTime('two_factor_enforced_at')->setNotnull(false)->setDefault(null);
            });
        }

        // 2) Create user_trusted_devices
        if (!$builder->hasTable("user_trusted_devices")) {
            $builder->create('user_trusted_devices', function (Table $table) {
                $table->increments('id');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->setNotnull(false);
                $table->bigInteger("user_id")->setUnsigned(true);
                $table->string('device_identifier', 255);
                $table->string('device_name', 255);
                $table->string('ip_address', 45);
                $table->text('user_agent');
                $table->dateTime('trusted_at');
                $table->dateTime('expires_at');
                $table->dateTime('last_seen_at');
                $table->boolean('is_revoked')->setNotnull(true)->setDefault(false);
                $table->unique(["user_id", "device_identifier"], "utd_user_device_uniq");
                $table->index(["user_id", "is_revoked"], "utd_user_revoked_idx");
                $table->index(["expires_at"], "utd_expires_idx");
                $table->foreign("users", "user_id", "id", ["onDelete" => "CASCADE"]);
            });
        }

        // 3) Create two_factor_audit_log
        if (!$builder->hasTable("two_factor_audit_log")) {
            $builder->create('two_factor_audit_log', function (Table $table) {
                $table->increments('id');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->setNotnull(false);
                $table->bigInteger("user_id")->setUnsigned(true);
                $table->string('event_type', 64);
                $table->string('method', 32);
                $table->string('ip_address', 45);
                $table->text('user_agent');
                $table->json('metadata')->setNotnull(false)->setDefault(null);
                $table->index(["user_id", "event_type", "created_at"], "tfa_user_event_created_idx");
                $table->index(["created_at"], "tfa_created_idx");
                $table->foreign("users", "user_id", "id", ["onDelete" => "CASCADE"]);
            });
        }

        // 4) Create user_recovery_codes
        if (!$builder->hasTable("user_recovery_codes")) {
            $builder->create('user_recovery_codes', function (Table $table) {
                $table->increments('id');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->setNotnull(false);
                $table->bigInteger("user_id")->setUnsigned(true);
                $table->string('code_hash', 72)->setNotnull(true);
                $table->dateTime('used_at')->setNotnull(false)->setDefault(null);
                $table->index(["user_id", "used_at"], "urc_user_used_idx");
                $table->unique(["user_id", "code_hash"], "urc_user_codehash_uniq");
                $table->foreign("users", "user_id", "id", ["onDelete" => "CASCADE"]);
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("user_recovery_codes")) {
            $builder->drop('user_recovery_codes');
        }
        if ($builder->hasTable("two_factor_audit_log")) {
            $builder->drop('two_factor_audit_log');
        }
        if ($builder->hasTable("user_trusted_devices")) {
            $builder->drop('user_trusted_devices');
        }
        if ($schema->hasTable("users") && $builder->hasColumn("users", "two_factor_enabled")) {
            $builder->table('users', function (Table $table) {
                $table->dropColumn('two_factor_enforced_at');
                $table->dropColumn('two_factor_method');
                $table->dropColumn('two_factor_enabled');
            });
        }
    }
}