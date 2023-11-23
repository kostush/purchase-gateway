<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCC3DSPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use Tests\UnitTestCase;

class RocketgateCC3DSPurchaseEventTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException
     * @return void
     */
    public function it_should_return_a_rocketgate_cc_purchase_import_event(): void
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCC3DSPurchaseEvent = new RocketgateCC3DSPurchaseImportEvent(
            $this->createRocketgateCCRetrieveTransactionResultMocks(
                $this->faker->uuid,
                $this->faker->uuid,
                $this->faker->uuid,
                [],
                true
            ),
            $purchaseProcessedEvent,
            null
        );

        $this->assertInstanceOf(RocketgateCCPurchaseImportEvent::class, $rocketgateCC3DSPurchaseEvent);
    }
}
