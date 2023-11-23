<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20191120133313 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'business_groups',
            function (Table $table) {
                $table->guid('business_group_id')->setLength(50);
                $table->primary('business_group_id');
                $table->text('public_keys');
                $table->string('private_key', 36);
                $table->string('descriptor')->setNotnull(false);
            }
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('business_groups');
    }
}
