<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
use ProBillerNG\PurchaseGateway\Domain\EventTracker;
use Ramsey\Uuid\Uuid;

class Version20190212152106 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create('event_tracker', function (Table $table) {
            $table->guid('event_tracker_id');
            $table->string('event_tracker_type');
            // Force datetime(6), doctrine does not support microseconds natively
            $table->getTable()
                ->addColumn('last_processed_event_date', 'datetime')
                ->setColumnDefinition('DATETIME(6)')
                ->setNotnull(false);
            $table->dateTimeTz('created_on');
            $table->dateTimeTz('updated_on');
            $table->primary('event_tracker_id');
        });
    }

    /**
     * Add proper tracker to db
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function postUp(Schema $schema) {
        $values = [
            Uuid::uuid4()->toString(),
            EventTracker::PURCHASE_DOMAIN_EVENT_TYPE,
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ];

        $insertValue = "'" . implode("','", $values) . "'";

        $this->connection->executeQuery("INSERT INTO `event_tracker` "
            . "(`event_tracker_id`, `event_tracker_type`, `created_on`, `updated_on`) "
            . "VALUES ({$insertValue})");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('event_tracker');
    }
}
