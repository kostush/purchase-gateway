<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190903135501 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'command_directives',
            function (Table $table) {
                $table->increments('id');
                $table->string('command_name')->setLength(50);
                $table->string('type')->setLength(50);
                $table->dateTime('created');
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->drop('command_directives');
    }
}
