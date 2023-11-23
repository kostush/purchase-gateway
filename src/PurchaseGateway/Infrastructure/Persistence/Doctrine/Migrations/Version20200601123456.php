<?php
namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20200601123456 extends AbstractMigration
{
    /**
     * @param Schema $schema Schema
     * @return void
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->table(
            'sites',
            function (Table $table) {
                $table->boolean('isStickyGateway')->setDefault(0);
            }
        );
    }

    /**
     * @param Schema $schema Schema
     * @return void
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->table(
            'sites',
            function (Table $table) {
                $table->dropColumn('isStickyGateway');
            }
        );
    }
}