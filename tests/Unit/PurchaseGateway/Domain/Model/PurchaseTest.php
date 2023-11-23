<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidTransactionStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use Tests\UnitTestCase;

class PurchaseTest extends UnitTestCase
{
    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_state_exception_when_status_not_defined_in_status_map(): void
    {
        $this->expectException(InvalidTransactionStateException::class);

        Purchase::create(
            $this->createMock(PurchaseId::class),
            $this->createMock(MemberId::class),
            $this->createMock(SessionId::class),
            $this->createMock(ProcessedItemsCollection::class),
            'not-defined-status-string'
        );
    }


    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_contain_the_success_status_when_transaction_approved_status_received(): void
    {
        $purchase = Purchase::create(
            $this->createMock(PurchaseId::class),
            $this->createMock(MemberId::class),
            $this->createMock(SessionId::class),
            $this->createMock(ProcessedItemsCollection::class),
            Transaction::STATUS_APPROVED
        );

        $this->assertSame(Purchase::STATUS_SUCCESS, $purchase->status());
    }
}
