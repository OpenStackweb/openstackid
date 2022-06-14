<?php

namespace Database\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20220614165317 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("users") && !$builder->hasColumn("users","created_by_otp_id") ) {
            $builder->table('users', function (Table $table) {
                $table->bigInteger('created_by_otp_id', false)->setNotnull(false)->setDefault('NULL');
                $table->index("created_by_otp_id", "created_by_otp_id");
                $table->foreign("oauth2_otp", "created_by_otp_id", "id", ["onDelete" => "SET NULL"]);
                $table->unique(["created_by_otp_id"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("users") && $builder->hasColumn("users","created_by_otp_id") ) {
            $builder->table('users', function (Table $table) {
                $table->dropColumn('created_by_otp_id');
            });
        }
    }
}
