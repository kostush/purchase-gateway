<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190321140241 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE items SET item_id=(items.transaction_id);");
        (new Builder($schema))->table('items', function(Table $table) {
            $table->unique('item_id');
        });
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->table('items', function(Table $table) {
            $table->getTable()->dropIndex('UNIQ_E11EE94D126F525E');
        });
    }
}
