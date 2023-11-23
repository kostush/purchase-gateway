<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCC3DS2PurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use Tests\UnitTestCase;

class RocketgateCC3DS2PurchaseEventTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws UnknownBillerIdException
     * @throws \Exception
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_return_a_rocketgate_3ds2_cc_purchase_import_event(): void
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCC3DS2PurchaseEvent = new RocketgateCC3DS2PurchaseImportEvent(
            $this->createRocketgateCCRetrieveTransactionResultMocks(
                $this->faker->uuid,
                $this->faker->uuid,
                $this->faker->uuid,
                [],
                true,
                null,
                null,
                2
            ),
            $purchaseProcessedEvent,
            null
        );

        $this->assertInstanceOf(RocketgateCC3DS2PurchaseImportEvent::class, $rocketgateCC3DS2PurchaseEvent);
    }
}
