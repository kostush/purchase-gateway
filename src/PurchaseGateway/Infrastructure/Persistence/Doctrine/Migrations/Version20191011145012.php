<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20191011145012 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'bundles',
            function (Table $table) {
                $table->increments('id');
                $table->guid('bundle_id')->setLength(50);
                $table->guid('addon_id')->setLength(50);
                $table->string('addon_type', 36);
                $table->boolean('require_active_content');

                $table->unique(['bundle_id', 'addon_id']);
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('bundles');
    }
}
