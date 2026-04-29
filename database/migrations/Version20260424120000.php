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

/**
 * Class Version20260424120000
 * @package Database\Migrations
 *
 * Enforce uniqueness of (user_id, device_identifier) on user_trusted_devices.
 * Replaces the plain index utd_user_device_idx with a unique one so that a
 * given user can never accumulate duplicate device rows.
 */
final class Version20260424120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {

        $duplicates = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (
            SELECT 1
            FROM user_trusted_devices
            GROUP BY user_id, device_identifier
            HAVING COUNT(*) > 1
        ) dup'
        );

        $this->abortIf(
            $duplicates > 0,
            'Duplicate trusted devices exist; dedupe user_trusted_devices before applying utd_user_device_uniq.'
        );

        $this->addSql(
            'ALTER TABLE user_trusted_devices
             DROP INDEX utd_user_device_idx,
             ADD  UNIQUE INDEX utd_user_device_uniq (user_id, device_identifier)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE user_trusted_devices
             DROP INDEX utd_user_device_uniq,
             ADD  INDEX utd_user_device_idx (user_id, device_identifier)'
        );
    }
}