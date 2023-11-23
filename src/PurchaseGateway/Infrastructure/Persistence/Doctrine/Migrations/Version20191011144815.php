<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20191011144815 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'addons',
            function (Table $table) {
                $table->guid('addon_id');
                $table->string('type', 36)->setNotnull(false);
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('addons');
    }
}
