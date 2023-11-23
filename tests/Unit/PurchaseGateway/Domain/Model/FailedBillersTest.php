<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\FailedBillersNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InitializedItemsCollectionNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\PurchaseWasSuccessfulException;
use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;
use Tests\UnitTestCase;

class FailedBillersTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_if_the_purchase_was_successful(): void
    {
        $this->expectException(PurchaseWasSuccessfulException::class);

        $initializedItem = $this->getMockBuilder(InitializedItem::class)
            ->onlyMethods(['wasItemPurchaseSuccessful'])
            ->disableOriginalConstructor()
            ->getMock();
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);

        $initializedItemCollection = new InitializedItemCollection();
        $initializedItemCollection->add($initializedItem);

        FailedBillers::createFromInitializedItemCollection($initializedItemCollection);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_if_no_failed_billers_are_found(): void
    {
        $this->expectException(InitializedItemsCollectionNotFoundException::class);

        FailedBillers::createFromInitializedItemCollection(new InitializedItemCollection());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_if_no_transactions_are_found(): void
    {
        $this->expectException(FailedBillersNotFoundException::class);

        $initializedItem = $this->getMockBuilder(InitializedItem::class)
            ->onlyMethods(['wasItemPurchaseSuccessful', 'transactionCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('transactionCollection')->willReturn(new TransactionCollection());

        $initializedItemCollection = new InitializedItemCollection();
        $initializedItemCollection->add($initializedItem);

        FailedBillers::createFromInitializedItemCollection($initializedItemCollection);
    }

    /**
     * @test
     * @throws \Exception
     * @return FailedBillers
     */
    public function it_should_return_a_failed_billers_object(): array
    {
        $transactionCollection = new TransactionCollection();
        $transactionCollection->add(
            $this->getTransactionMock(NetbillingBiller::BILLER_NAME, Transaction::STATUS_ABORTED)
        );
        $transactionCollection->add(
            $this->getTransactionMock(RocketgateBiller::BILLER_NAME, Transaction::STATUS_ABORTED)
        );
        $transactionCollection->add(
            $this->getTransactionMock(RocketgateBiller::BILLER_NAME, Transaction::STATUS_DECLINED)
        );

        $initializedItemCollection = new InitializedItemCollection();
        $initializedItemCollection->add($this->getInitializedItemMock($transactionCollection));
        $initializedItemCollection->add($this->getInitializedItemMock(new TransactionCollection()));

        $failedBillers = FailedBillers::createFromInitializedItemCollection($initializedItemCollection);

        $this->assertInstanceOf(FailedBillers::class, $failedBillers);

        return $failedBillers->toArray();
    }

    /**
     * @param string $billerName The biller name
     * @param string $status     The transaction status
     * @return Transaction
     */
    private function getTransactionMock(string $billerName, string $status): Transaction
    {
        $transactionMock = $this->getMockBuilder(Transaction::class)
            ->onlyMethods(['billerName', 'state'])
            ->disableOriginalConstructor()
            ->getMock();
        $transactionMock->method('billerName')->willReturn($billerName);
        $transactionMock->method('state')->willReturn($status);

        return $transactionMock;
    }

    /**
     * @param TransactionCollection $transactionCollection The transaction Collection
     * @return InitializedItem
     */
    private function getInitializedItemMock(TransactionCollection $transactionCollection): InitializedItem
    {
        $initializedItem = $this->getMockBuilder(InitializedItem::class)
            ->onlyMethods(['transactionCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $initializedItem->method('transactionCollection')->willReturn($transactionCollection);

        return $initializedItem;
    }

    /**
     * @test
     * @depends it_should_return_a_failed_billers_object
     * @param array $failedBillers The failed billers array
     * @return void
     */
    public function the_returned_array_should_contain_the_correct_billers(array $failedBillers): void
    {
        $foundRocketgate = false;
        $foundNetbilling = false;
        $count           = 0;
        foreach ($failedBillers as $key => $biller) {
            if ($biller['billerName'] === RocketgateBiller::BILLER_NAME) {
                $count++;
                $foundRocketgate = true;
            }
            if ($biller['billerName'] === NetbillingBiller::BILLER_NAME) {
                $count++;
                $foundNetbilling = true;
            }
        }
        $this->assertTrue($foundRocketgate);
        $this->assertTrue($foundNetbilling);
        $this->assertEquals(2, $count);
    }

    /**
     * @test
     * @param array $failedBillers The failed Billers array
     * @depends it_should_return_a_failed_billers_object
     * @return void
     */
    public function failed_billers_should_not_contain_duplicate_biller_names(array $failedBillers): void
    {
        //3 transactions were provided in the mock collection, 2 biller names should be returned
        $this->assertEquals(2, count($failedBillers));
    }
}
