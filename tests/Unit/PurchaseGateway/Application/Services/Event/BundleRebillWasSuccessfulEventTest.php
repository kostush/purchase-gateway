<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use DateTimeImmutable;
use Exception;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasSuccessfulEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Tests\UnitTestCase;

class BundleRebillWasSuccessfulEventTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $itemId;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $memberId;

    /**
     * @var string
     */
    private $bundleId;

    /**
     * Init
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->itemId    = '65ef6a79-2b40-4d06-a581-450101993082';
        $this->sessionId = 'fed82c04-2c89-4290-ba34-978ef7b7e001';
        $this->memberId  = '94cf5e4d-85b3-44e4-bce2-494e27a38ffc';
        $this->bundleId  = 'f9d9ff5b-80fc-4c90-8267-bbd35125cd75';
    }

    /**
     * @test
     * @return BundleRebillWasSuccessfulEvent
     * @throws Exception
     */
    public function it_should_return_a_bundle_rebill_was_successful_event_object(): BundleRebillWasSuccessfulEvent
    {
        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed->method('itemId')->willReturn($this->itemId);
        $purchaseProcessed->method('rebillFrequency')->willReturn(30);
        $purchaseProcessed->method('sessionId')->willReturn($this->sessionId);
        $purchaseProcessed->method('memberId')->willReturn($this->memberId);
        $purchaseProcessed->method('bundleId')->willReturn($this->bundleId);


        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('createdAt')->willReturn(new DateTimeImmutable());

        $bundleRebillWasSuccessfulEvent = BundleRebillWasSuccessfulEvent::createFromPurchase(
            $purchaseProcessed,
            $transactionInformation
        );

        $this->assertInstanceOf(BundleRebillWasSuccessfulEvent::class, $bundleRebillWasSuccessfulEvent);

        return $bundleRebillWasSuccessfulEvent;
    }

    /**
     * @test
     * @param BundleRebillWasSuccessfulEvent $bundleRebillWasSuccessfulEvent Bundle rebill was successful event.
     * @depends it_should_return_a_bundle_rebill_was_successful_event_object
     * @return void
     * @throws Exception
     */
    public function it_should_return_an_array_with_correct_data(BundleRebillWasSuccessfulEvent $bundleRebillWasSuccessfulEvent): void
    {
        $result = true;

        $data = $bundleRebillWasSuccessfulEvent->toArray();

        if ($data['type'] !== BundleRebillWasSuccessfulEvent::INTEGRATION_NAME
            || $data['sessionId'] !== $this->sessionId
            || $data['memberId'] !== $this->memberId
            || $data['itemId'] !== $this->itemId
            || $data['bundleId'] !== $this->bundleId
            || $data['rebillDate'] !== $bundleRebillWasSuccessfulEvent->rebillDate()
            || $data['withCharge'] !== true
            || $data['occurredOn'] !== $bundleRebillWasSuccessfulEvent->occurredOn()

        ) {
            $result = false;
        }

        $this->assertTrue($result);
    }
}
