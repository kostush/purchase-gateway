<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\Event\RocketgatePurchasedItemDetails;
use Tests\UnitTestCase;

class RocketgatePurchasedItemDetailsTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_create_tax_payload_from_transaction_amount_if_not_present_on_event()
    {
        /** @var MockObject | RocketgatePurchasedItemDetails $rgPurchaseItemDetails */
        $rgPurchaseItemDetails = $this->getMockBuilder(RocketgatePurchasedItemDetails::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $tax = $rgPurchaseItemDetails->createTaxPayloadFromAmounts(10, null);

        $this->assertArrayHasKey('initialAmount', $tax);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_add_rebill_amount_to_tax_payload_from_transaction_rebill_amount_if_not_present_on_event()
    {
        /** @var MockObject | RocketgatePurchasedItemDetails $rgPurchaseItemDetails */
        $rgPurchaseItemDetails = $this->getMockBuilder(RocketgatePurchasedItemDetails::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $tax = $rgPurchaseItemDetails->createTaxPayloadFromAmounts(10, 10);

        $this->assertArrayHasKey('rebillAmount', $tax);
    }
}
