<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190613151345 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @return void
     */
    public function up(Schema $schema): void
    {
        (new Builder($schema))->table(
            'stored_events',
            function (Table $table) {
                $table->dropColumn('version');
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @return void
     */
    public function down(Schema $schema): void
    {
        (new Builder($schema))->table(
            'stored_events',
            function (Table $table) {
                $table->smallInteger('version');
            }
        );
    }
}
