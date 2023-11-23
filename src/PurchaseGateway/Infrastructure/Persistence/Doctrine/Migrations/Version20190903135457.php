<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190903135457 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'projection_position_ledgers',
            function (Table $table) {
                $table->increments('id');
                $table->string('name')->setLength(50);
                $table->string('origin')->setLength(50);
                $table->getTable()
                    ->addColumn('position', 'datetime')
                    ->setColumnDefinition('DATETIME(6)')
                    ->setNotnull(false);
                $table->dateTime('last_modified');
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->drop('projection_position_ledgers');
    }
}
