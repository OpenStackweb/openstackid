<?php
/**
 * Copyright 2023 OpenStack Foundation
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

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

/**
 * Class Version20230601162315
 * @package Database\Migrations
 */
final class Version20230601162315 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users MODIFY public_profile_show_fullname tinyint(1) DEFAULT '1' NOT NULL;");
        $this->addSql("ALTER TABLE users MODIFY public_profile_allow_chat_with_me tinyint(1) DEFAULT '1' NOT NULL;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users MODIFY public_profile_show_fullname tinyint(1) DEFAULT '0' NOT NULL;");
        $this->addSql("ALTER TABLE users MODIFY public_profile_allow_chat_with_me tinyint(1) DEFAULT '0' NOT NULL;");
    }
}
