<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190208210958 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'stored_events',
            function (Table $table) {
                $table->guid('event_id');
                $table->primary('event_id');
                $table->guid('aggregate_id');
                $table->text('event_body');
                $table->string('type_name');
                // Force datetime(6), doctrine does not support microseconds natively
                $table->getTable()
                    ->addColumn('occurred_on', 'datetime')
                    ->setColumnDefinition('DATETIME(6)')
                    ->setNotnull(false);
                $table->index(['aggregate_id']);
            }
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('stored_events');
    }
}
