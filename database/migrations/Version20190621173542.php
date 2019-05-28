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
/**
 * Class Version20190621173542
 * @package Database\Migrations
 */
class Version20190621173542 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE openid_trusted_sites DROP FOREIGN KEY openid_trusted_sites_user_id_foreign;
ALTER TABLE openid_trusted_sites
ADD CONSTRAINT openid_trusted_sites_user_id_foreign
FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE user_actions DROP FOREIGN KEY user_actions_user_id_foreign;
ALTER TABLE user_actions
ADD CONSTRAINT user_actions_user_id_foreign
FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE oauth2_client DROP FOREIGN KEY oauth2_client_user_id_foreign;
ALTER TABLE oauth2_client
ADD CONSTRAINT oauth2_client_user_id_foreign
FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE oauth2_client DROP FOREIGN KEY oauth2_client_edited_by_id_foreign;
ALTER TABLE oauth2_client
ADD CONSTRAINT oauth2_client_edited_by_id_foreign
FOREIGN KEY (edited_by_id) REFERENCES users (id) ON DELETE SET NULL;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
