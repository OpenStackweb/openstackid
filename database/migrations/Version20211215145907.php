<?php

namespace Database\Migrations;

use App\libs\OAuth2\IUserScopes;
use Database\Seeders\SeedUtils;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20211215145907 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        SeedUtils::seedScopes([
            [
                'name'               => IUserScopes::Write,
                'short_description'  => 'Allows access to write Users Profile',
                'description'        => 'Allows access to write Users Profile',
                'system'             => false,
                'default'            => false,
                'groups'             => false,
            ],

        ], 'users');

        SeedUtils::seedApiEndpoints('users', [
            [
                'name' => 'update-user',
                'active' => true,
                'route' => '/api/v1/users/{id}',
                'http_method' => 'PUT',
                'scopes' => [
                    \App\libs\OAuth2\IUserScopes::Write
                ],
            ],
        ]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
