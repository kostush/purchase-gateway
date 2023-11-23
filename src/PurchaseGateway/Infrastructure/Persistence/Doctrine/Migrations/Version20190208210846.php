<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190208210846 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'purchase_items',
            function (Table $table) {
                $table->guid('purchase_id');
                $table->guid('transaction_id');
                $table->primary(['purchase_id', 'transaction_id']);
                $table->unique('transaction_id');
                $table->foreign('purchases', 'purchase_id', 'purchase_id');
                $table->foreign('items', 'transaction_id', 'transaction_id');
            }
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('purchase_items');
    }
}
