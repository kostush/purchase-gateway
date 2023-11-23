<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20191011161322 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->table(
            'projection_position_ledgers',
            function (Table $table) {
                $table->getTable()->changeColumn(
                    'position',
                    [
                        'Type'    => Type::getType('string'),
                        'Length'  => 50,
                        'Notnull' => false
                    ]
                );
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->table(
            'projection_position_ledgers',
            function (Table $table) {
                $table->dateTime('position')->setNotnull(false)->change();
                $table->getTable()->changeColumn(
                    'position',
                    [
                        'Type'    => Type::getType('datetime'),
                        'Notnull' => false
                    ]
                );
            }
        );
    }
}
