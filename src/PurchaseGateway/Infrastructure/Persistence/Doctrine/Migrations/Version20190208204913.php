<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190208204913 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'purchases',
            function (Table $table) {
                $table->guid('purchase_id');
                $table->guid('member_id');
                $table->guid('session_id');
                $table->dateTime('created_at');
                $table->primary('purchase_id');
            }
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('purchases');
    }
}
