<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use ProBillerNG\PurchaseGateway\Domain\EventTracker;
use Ramsey\Uuid\Uuid;

class Version20190604101829 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $values = [
            Uuid::uuid4()->toString(),
            EventTracker::PAYMENT_TEMPLATE_CREATED_TYPE,
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
        $this->connection->executeQuery("DELETE FROM  `event_tracker`  where `event_tracker_type`="
                                        . EventTracker::PAYMENT_TEMPLATE_CREATED_TYPE );
    }
}
