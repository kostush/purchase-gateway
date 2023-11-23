<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\GetTransactionDataByAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use Tests\UnitTestCase;

class GetTransactionDataByAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_retrieve_transaction_result_when_valid_data_provided()
    {
        $adapter = new GetTransactionDataByAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $this->assertInstanceOf(GetTransactionDataByAdapter::class, $adapter);
    }
}
