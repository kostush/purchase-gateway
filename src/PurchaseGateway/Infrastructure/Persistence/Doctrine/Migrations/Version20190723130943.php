<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190723130943 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'processed_items',
            function (Table $table) {
                $table->guid('item_id');
                $table->string('type', 36);
                $table->guid('bundle_id');
                $table->text('addon_ids');
                $table->text('subscription_details');
                $table->text('transaction_details');
                $table->primary('item_id');
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('processed_items');
    }
}
