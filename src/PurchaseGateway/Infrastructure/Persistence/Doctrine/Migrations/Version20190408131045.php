<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types\SubscriptionInfoJsonSerializer;

class Version20190408131045 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $subscriptionJson = json_encode(
            [
                'subscriptionId' => null,
                'username'       => null
            ]
        );
        $this->addSql("UPDATE items SET subscription_details='" . $subscriptionJson . "' where status='declined'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
