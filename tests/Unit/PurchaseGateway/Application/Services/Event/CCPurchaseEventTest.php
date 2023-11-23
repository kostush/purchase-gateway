<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use Tests\UnitTestCase;

class CCPurchaseEventTest extends UnitTestCase
{

    /**
     * @test
     * @return RocketgateCCPurchaseImportEvent
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException
     * @throws \Exception
     */
    public function it_should_return_a_cc_purchase_event_object_if_correct_data_is_sent(): RocketgateCCPurchaseImportEvent
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCCPurchaseEvent = new RocketgateCCPurchaseImportEvent(
            $this->createRocketgateCCRetrieveTransactionResultMocks(),
            $purchaseProcessedEvent,
            null
        );

        $this->assertInstanceOf(RocketgateCCPurchaseImportEvent::class, $rocketgateCCPurchaseEvent);

        return $rocketgateCCPurchaseEvent;
    }

    /**
     * @test
     *
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurchaseEvent RocketgateCCPurchaseEvent
     *
     * @depends it_should_return_a_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_first_6(RocketgateCCPurchaseImportEvent $rocketgateCCPurchaseEvent)
    {
        $this->assertEquals('123456', $rocketgateCCPurchaseEvent->first6());
    }

    /**
     * @test
     *
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurchaseEvent RocketgateCCPurchaseEvent
     *
     * @depends it_should_return_a_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_last_4(RocketgateCCPurchaseImportEvent $rocketgateCCPurchaseEvent)
    {
        $this->assertEquals('4444', $rocketgateCCPurchaseEvent->last4());
    }

    /**
     * @test
     *
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException
     */
    public function it_should_contain_a_card_expiration_year()
    {
        $cardExpirationYear = 2027;
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCCPurchaseEvent = new RocketgateCCPurchaseImportEvent(
            $this->createRocketgateCCRetrieveTransactionResultMocks(
                null,
                null,
                null,
                [],
                false,
                null,
                $cardExpirationYear
                ),
            $purchaseProcessedEvent,
            null
        );

        $this->assertEquals($cardExpirationYear, $rocketgateCCPurchaseEvent->cardExpirationYear());
    }

    /**
     * @test
     *
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurchaseEvent RocketgateCCPurchaseEvent
     *
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException
     * @depends it_should_return_a_cc_purchase_event_object_if_correct_data_is_sent
     */
    public function it_should_contain_a_card_expiration_month()
    {
        $cardExpirationMonth = 11;
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCCPurchaseEvent = new RocketgateCCPurchaseImportEvent(
            $this->createRocketgateCCRetrieveTransactionResultMocks(
                null,
                null,
                null,
                [],
                false,
                $cardExpirationMonth,
                null
            ),
            $purchaseProcessedEvent,
            null
        );

        $this->assertEquals($cardExpirationMonth, $rocketgateCCPurchaseEvent->cardExpirationMonth());
    }
}
