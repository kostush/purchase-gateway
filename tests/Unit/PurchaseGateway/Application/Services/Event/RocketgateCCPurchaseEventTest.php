<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgateCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use Tests\UnitTestCase;

class RocketgateCCPurchaseEventTest extends UnitTestCase
{
    private $merchantId;

    private $invoiceId;

    private $customerId;


    /**
     * Init
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->merchantId = '6b4d4432-743a-485e-9373-fe318f72e3f3';
        $this->invoiceId  = '4caac2be-c3f0-4fc2-a017-15f6157cb558';
        $this->customerId = '113f545f-7b76-41df-b0d1-d7ff053247cd';
    }

    /**
     * @test
     * @return RocketgateCCPurchaseImportEvent
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException
     * @throws \Exception
     */
    public function it_should_return_a_rocketgate_cc_purchase_event_object_if_correct_data_is_sent()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $rocketgateCCPurchaseEvent = new RocketgateCCPurchaseImportEvent(
            $this->createRocketgateCCRetrieveTransactionResultMocks(
                $this->merchantId,
                $this->invoiceId,
                $this->customerId,
            ),
            $purchaseProcessedEvent,
            null
        );

        $this->assertInstanceOf(RocketgateCCPurchaseImportEvent::class, $rocketgateCCPurchaseEvent);
        return $rocketgateCCPurchaseEvent;
    }

    /**
     * @test
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent RocketgateCCPurchaseEvent
     * @depends it_should_return_a_rocketgate_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_merchant_id(RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent)
    {
        $this->assertEquals($this->merchantId, $rocketgateCCPurcahseEvent->merchantId());
    }

    /**
     * @test
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent RocketgateCCPurchaseEvent
     * @depends it_should_return_a_rocketgate_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_merchant_password(RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent)
    {
        $this->assertEquals('password', $rocketgateCCPurcahseEvent->merchantPassword());
    }

    /**
     * @test
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent RocketgateCCPurchaseEvent
     * @depends it_should_return_a_rocketgate_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_card_hash(RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent)
    {
        $this->assertEquals('123456789', $rocketgateCCPurcahseEvent->cardHash());
    }

    /**
     * @test
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent RocketgateCCPurchaseEvent
     * @depends it_should_return_a_rocketgate_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_merchant_account(RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent)
    {
        $this->assertEquals('MerchantAccount', $rocketgateCCPurcahseEvent->merchantAccount());
    }

    /**
     * @test
     * @param RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent RocketgateCCPurchaseEvent
     * @depends it_should_return_a_rocketgate_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_card_description(RocketgateCCPurchaseImportEvent $rocketgateCCPurcahseEvent)
    {
        $this->assertEquals('CREDIT', $rocketgateCCPurcahseEvent->cardDescription());
    }
}
