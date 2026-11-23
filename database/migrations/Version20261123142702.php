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

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20261123142702
 * Adds the per-client refresh token lifetime override.
 * NULL = inherit the global OAuth2.RefreshToken.Lifetime, 0 = never expires, N > 0 = N seconds.
 * @package Database\Migrations
 */
final class Version20261123142702 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($schema->hasTable("oauth2_client") && !$builder->hasColumn("oauth2_client", "refresh_token_lifetime")) {
            $builder->table('oauth2_client', function (Table $table) {
                $table->unsignedInteger('refresh_token_lifetime')->setNotnull(false)->setDefault(null);
            });
        }
    }

    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($schema->hasTable("oauth2_client") && $builder->hasColumn("oauth2_client", "refresh_token_lifetime")) {
            $builder->table('oauth2_client', function (Table $table) {
                $table->dropColumn('refresh_token_lifetime');
            });
        }
    }
}
