<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20200205113448 extends AbstractMigration
{
    /**
     * @param Schema $schema schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'failed_event_publish',
            function (Table $table) {
                $table->increments('id');
                $table->guid('aggregate_id')->setLength(36);
                $table->integer('published')->setDefault(0);
                $table->integer('retries')->setDefault(0);
                $table->getTable()->addColumn('last_attempted', 'datetime')
                    ->setColumnDefinition('DATETIME(6)')
                    ->setNotnull(false);
                $table->getTable()->addColumn('timestamp', 'datetime')
                    ->setColumnDefinition('DATETIME(6)')
                    ->setNotnull(false);

                $table->unique(['aggregate_id']);
            }
        );
    }

    /**
     * @param Schema $schema schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('failed_event_publish');
    }
}
