<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20220531175731 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow to emit refresh tokens (offline access without user presence).',
    short_description = 'Allow to emit refresh tokens (offline access without user presence).'
WHERE name = 'offline_access';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET short_description = 'Allow access to your profile info.'
WHERE name = 'profile';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET short_description = 'Allow access to your email info.'
WHERE name = 'email';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET short_description = 'Allow access to your address info.'
WHERE name = 'address';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow to request user registrations.',
    short_description = 'Allow to request user registrations.'
WHERE name = 'user-registration';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET short_description = 'Allow access to users info.'
WHERE name = 'users-read-all';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow SSO integration.',
    short_description = 'Allow SSO integration.'
WHERE name = 'sso';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow access to read your Profile.',
    short_description = 'Allow access to read your Profile.'
WHERE name = 'me/read';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow access to write your Profile.',
    short_description = 'Allow access to write your Profile.'
WHERE name = 'me/write';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow to seed channel types.',
    short_description = 'Allow to seed channel types.'
WHERE name = 'channel-types/seed';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE oauth2_api_scope 
SET description = 'Allow access to write Users Profile.',
    short_description = 'Allow access to write Users Profile.'
WHERE name = 'users/write';
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
