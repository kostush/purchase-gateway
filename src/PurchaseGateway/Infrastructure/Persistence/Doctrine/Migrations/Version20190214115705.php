<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190214115705 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'jobs',
            function (Table $table) {
                $table->bigIncrements('id');
                $table->string('queue');
                $table->text('payload');
                $table->smallInteger('attempts');
                $table->unsignedInteger('reserved_at')->setNotnull(false);
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
                $table->index(['queue', 'reserved_at']);
            }
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('jobs');
    }
}
