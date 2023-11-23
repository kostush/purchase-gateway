<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseProcessedEnrichedEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Tests\UnitTestCase;

class PurchaseProcessedEnrichedEventTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_purchase_processed_event_from_new_cc_purchase_data()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData())
        );

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')->willReturn(1);
        $transactionInformation->method('rebillFrequency')->willReturn(1);

        $bundles = [];

        $bundles[$purchaseProcessed->bundleId()] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->bundleId()),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->addOnId()),
            AddonType::create(AddonType::CONTENT)
        );

        $bundles[$purchaseProcessed->crossSalePurchaseData()[0]['bundleId']] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['bundleId']),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['addonId']),
            AddonType::create(AddonType::CONTENT)
        );

        $this->assertInstanceOf(
            PurchaseProcessedEnrichedEvent::class,
            PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
                $purchaseProcessed,
                $transactionInformation,
                $bundles,
                $this->createSite()
            )
        );
    }

    /**
     * @test
     * @return PurchaseProcessedEnrichedEvent
     * @throws \Exception
     */
    public function it_should_return_a_purchase_processed_event_from_payment_template_purchase_data()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode($this->createPurchaseProcessedExistingPaymentEventData())
        );

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')->willReturn(1);
        $transactionInformation->method('rebillFrequency')->willReturn(1);

        $bundles = [];

        $bundles[$purchaseProcessed->bundleId()] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->bundleId()),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->addOnId()),
            AddonType::create(AddonType::CONTENT)
        );

        $bundles[$purchaseProcessed->crossSalePurchaseData()[0]['bundleId']] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['bundleId']),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['addonId']),
            AddonType::create(AddonType::CONTENT)
        );

        $purchaseProcessedEnrichedEvent = PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
            $purchaseProcessed,
            $transactionInformation,
            $bundles,
            $this->createSite()
        );

        $this->assertInstanceOf(
            PurchaseProcessedEnrichedEvent::class,
            $purchaseProcessedEnrichedEvent
        );

        return $purchaseProcessedEnrichedEvent;
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_processed_event_from_payment_template_purchase_data
     * @param PurchaseProcessedEnrichedEvent $purchaseProcessedEnrichedEvent Event
     * @return void
     */
    public function to_array_should_return_all_mandatory_keys(
        PurchaseProcessedEnrichedEvent $purchaseProcessedEnrichedEvent
    ) {
        $mandatoryKeys = [
            'type',
            'purchaseId',
            'sessionId',
            'memberId',
            'siteId',
            'amount',
            'subscriptionId',
            'crossSellPurchaseData',
            'itemId',
            'bundleId',
            'addOnId',
            'isTrial',
            'addOnType',
            'initialDays',
            'isUnlimited',
            'isNsfOnPurchase',
            'isMigrated',
            'requireActiveContent',
            'memberExists',
            'occurredOn',
        ];

        $arrayEventData = $purchaseProcessedEnrichedEvent->toArray();

        foreach ($mandatoryKeys as $key) {
            if (!isset($arrayEventData[$key])) {
                $this->fail('Missing data from array for field: ' . $key);
            }
        }

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_not_have_xSell_when_entry_site_does_not_have_nsf_enabled_transaction_is_declined_and_is_not_nsf() : void
    {
        $data = [
            'transactionCollectionCrossSale' => [
                [
                    'state' => Transaction::STATUS_DECLINED,
                    'isNsf' => false,
                    'transactionId' => $this->faker->uuid
                ]
            ]
        ];

        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData($data))
        );

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')->willReturn(1);
        $transactionInformation->method('rebillFrequency')->willReturn(1);

        $bundles = [];

        $bundles[$purchaseProcessed->bundleId()] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->bundleId()),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->addOnId()),
            AddonType::create(AddonType::CONTENT)
        );

        $bundles[$purchaseProcessed->crossSalePurchaseData()[0]['bundleId']] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['bundleId']),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['addonId']),
            AddonType::create(AddonType::CONTENT)
        );

        $purchaseProcessedEnrichedEvent = PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
            $purchaseProcessed,
            $transactionInformation,
            $bundles,
            $this->createSite()
        );

        $this->assertInstanceOf(PurchaseProcessedEnrichedEvent::class, $purchaseProcessedEnrichedEvent);
        $this->assertEmpty($purchaseProcessedEnrichedEvent->crossSalePurchaseData());
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_not_have_xSell_when_transaction_entry_site_does_not_have_nsf_enabled_transaction_is_declined_and_is_nsf() : void
    {
        $data = [
            'transactionCollectionCrossSale' => [
                [
                    'state' => Transaction::STATUS_DECLINED,
                    'isNsf' => true,
                    'transactionId' => $this->faker->uuid
                ]
            ]
        ];

        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData($data))
        );

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')->willReturn(1);
        $transactionInformation->method('rebillFrequency')->willReturn(1);

        $bundles = [];

        $bundles[$purchaseProcessed->bundleId()] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->bundleId()),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->addOnId()),
            AddonType::create(AddonType::CONTENT)
        );

        $bundles[$purchaseProcessed->crossSalePurchaseData()[0]['bundleId']] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['bundleId']),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['addonId']),
            AddonType::create(AddonType::CONTENT)
        );

        $purchaseProcessedEnrichedEvent = PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
            $purchaseProcessed,
            $transactionInformation,
            $bundles,
            $this->createSite()
        );

        $this->assertInstanceOf(PurchaseProcessedEnrichedEvent::class, $purchaseProcessedEnrichedEvent);
        $this->assertEmpty($purchaseProcessedEnrichedEvent->crossSalePurchaseData());
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_not_have_xSell_when_transaction_entry_site_does_have_nsf_enabled_transaction_is_declined_and_is_not_nsf() : void
    {
        $data = [
            'transactionCollectionCrossSale' => [
                [
                    'state' => Transaction::STATUS_DECLINED,
                    'isNsf' => false,
                    'transactionId' => $this->faker->uuid
                ]
            ]
        ];

        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData($data))
        );

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')->willReturn(1);
        $transactionInformation->method('rebillFrequency')->willReturn(1);

        $bundles = [];

        $bundles[$purchaseProcessed->bundleId()] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->bundleId()),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->addOnId()),
            AddonType::create(AddonType::CONTENT)
        );

        $bundles[$purchaseProcessed->crossSalePurchaseData()[0]['bundleId']] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['bundleId']),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['addonId']),
            AddonType::create(AddonType::CONTENT)
        );

        $purchaseProcessedEnrichedEvent = PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
            $purchaseProcessed,
            $transactionInformation,
            $bundles,
            $this->createSite(false, true)
        );

        $this->assertInstanceOf(PurchaseProcessedEnrichedEvent::class, $purchaseProcessedEnrichedEvent);
        $this->assertEmpty($purchaseProcessedEnrichedEvent->crossSalePurchaseData());
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_have_xSell_when_transaction_entry_site_does_have_nsf_enabled_transaction_is_declined_and_is_nsf() : void
    {
        $data = [
            'transactionCollectionCrossSale' => [
                [
                    'state' => Transaction::STATUS_DECLINED,
                    'isNsf' => true,
                    'transactionId' => $this->faker->uuid
                ]
            ]
        ];

        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData($data))
        );

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')->willReturn(1);
        $transactionInformation->method('rebillFrequency')->willReturn(1);

        $bundles = [];

        $bundles[$purchaseProcessed->bundleId()] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->bundleId()),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->addOnId()),
            AddonType::create(AddonType::CONTENT)
        );

        $bundles[$purchaseProcessed->crossSalePurchaseData()[0]['bundleId']] = Bundle::create(
            BundleId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['bundleId']),
            $this->faker->boolean,
            AddonId::createFromString($purchaseProcessed->crossSalePurchaseData()[0]['addonId']),
            AddonType::create(AddonType::CONTENT)
        );

        $purchaseProcessedEnrichedEvent = PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
            $purchaseProcessed,
            $transactionInformation,
            $bundles,
            $this->createSite(false, true)
        );

        $this->assertInstanceOf(PurchaseProcessedEnrichedEvent::class, $purchaseProcessedEnrichedEvent);
        $this->assertNotEmpty($purchaseProcessedEnrichedEvent->crossSalePurchaseData());
    }
}
