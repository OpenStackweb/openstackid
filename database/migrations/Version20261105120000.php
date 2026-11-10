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
 * Class Version20261105120000
 * @package Database\Migrations
 *
 * Recovery-code batch acknowledgment (SDS idp-mfa.md §3.1, §3.5, §3.6):
 * adds users.recovery_codes_acknowledged_at and backfills it for users that
 * already hold unused codes.
 */
final class Version20261105120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if ($schema->hasTable("users") && !$builder->hasColumn("users", "recovery_codes_acknowledged_at")) {
            $builder->table('users', function (Table $table) {
                $table->dateTime('recovery_codes_acknowledged_at')->setNotnull(false)->setDefault(null);
            });
        }
    }

    /**
     * The backfill runs in postUp(): up() only records schema changes in the
     * $schema object (the SQL is computed from it after up() returns), so the
     * new column does not exist yet while up() executes.
     */
    public function postUp(Schema $schema): void
    {
        if (!$schema->hasTable("users") || !$schema->hasTable("user_recovery_codes")) {
            return;
        }

        // A user with at least one unused code is assumed to have seen the batch:
        // acknowledged_at = MAX(created_at) of that user's rows. Users without
        // unused codes (none, or only used) stay NULL.
        $this->connection->executeStatement(
            "UPDATE users u
             INNER JOIN (
                 SELECT user_id, MAX(created_at) AS batch_created_at
                 FROM user_recovery_codes
                 WHERE user_id IN (SELECT DISTINCT user_id FROM user_recovery_codes WHERE used_at IS NULL)
                 GROUP BY user_id
             ) c ON c.user_id = u.id
             SET u.recovery_codes_acknowledged_at = c.batch_created_at
             WHERE u.recovery_codes_acknowledged_at IS NULL"
        );
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if ($schema->hasTable("users") && $builder->hasColumn("users", "recovery_codes_acknowledged_at")) {
            $builder->table('users', function (Table $table) {
                $table->dropColumn('recovery_codes_acknowledged_at');
            });
        }
    }
}
