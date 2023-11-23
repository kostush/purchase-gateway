<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use ProBillerNG\PurchaseGateway\Domain\EventTracker;
use Ramsey\Uuid\Uuid;

class Version20190605092314 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $values = [
            Uuid::uuid4()->toString(),
            EventTracker::EVENT_TRACKER_TYPE_SEND_EMAIL,
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
        $this->connection->executeQuery("DELETE FROM `event_tracker`"
                                        . " WHERE `event_tracker_type` = " . EventTracker::EVENT_TRACKER_TYPE_SEND_EMAIL
        );
    }
}
