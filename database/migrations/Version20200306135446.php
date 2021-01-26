<?php namespace Database\Migrations;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class Version20200306135446
 * @package Database\Migrations
 */
class Version20200306135446 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
ALTER TABLE users MODIFY spam_type 
enum(
'None', 'Ham', 'Spam'
) default 'None' null;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE users_spam_estimator_feed MODIFY spam_type 
enum(
'None', 'Ham', 'Spam'
) default 'None' null;
SQL;
        $this->addSql($sql);

        // reset spam state to Ham
        $sql = <<<SQL
UPDATE users set spam_type = 'Ham';
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
