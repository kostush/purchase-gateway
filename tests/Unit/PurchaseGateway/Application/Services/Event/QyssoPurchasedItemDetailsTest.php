<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use Exception;
use ProBillerNG\PurchaseGateway\Application\Services\Event\QyssoDebitPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\QyssoPurchasedItemDetails;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerTransactionCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use Tests\UnitTestCase;

class QyssoPurchasedItemDetailsTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_qysso_purchased_item_details_object(): void
    {
        $purchaseDetails = [
            'state' => 'approved',
            'bundleId' => $this->faker->uuid,
            'addonId' => $this->faker->uuid,
            'itemId' => $this->faker->uuid,
            'initialDays' => 30,
            'subscriptionId' => $this->faker->uuid,
            'isTrial' => false
        ];

        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('amount')->willReturn(10.00);
        $transactionInformation->method('rebillAmount')->willReturn(10.00);

        $retrieveTransactionResult = $this->createMock(QyssoRetrieveTransactionResult::class);
        $retrieveTransactionResult->method('transactionInformation')->willReturn($transactionInformation);
        $retrieveTransactionResult->method('billerTransactions')->willReturn(new BillerTransactionCollection());

        $bundle = $this->createMock(Bundle::class);

        $qyssoPurchasedItemDetails = new QyssoPurchasedItemDetails(
            $purchaseDetails,
            $retrieveTransactionResult,
            $bundle,
            $this->faker->uuid
        );

        $this->assertInstanceOf(QyssoPurchasedItemDetails::class, $qyssoPurchasedItemDetails);
    }
}
