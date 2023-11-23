<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use Tests\UnitTestCase;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseIntegrationEventBuilder;

class PurchaseEventTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_return_a_purchase_event_object_if_correct_data_is_sent(): void
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCCPurchaseEvent = PurchaseIntegrationEventBuilder::build(
            $this->createRocketgateCCRetrieveTransactionResultMocks(),
            $purchaseProcessedEvent
        );

        $this->assertInstanceOf(RocketgateCCPurchaseImportEvent::class, $rocketgateCCPurchaseEvent);
    }
}
