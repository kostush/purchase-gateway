<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190723130946 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'purchase_processed_items',
            function (Table $table) {
                $table->guid('purchase_id');
                $table->guid('item_id');
                $table->primary(['purchase_id', 'item_id']);
                $table->foreign('purchases', 'purchase_id', 'purchase_id');
                $table->foreign('processed_items', 'item_id', 'item_id');
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('purchase_processed_items');
    }
}
