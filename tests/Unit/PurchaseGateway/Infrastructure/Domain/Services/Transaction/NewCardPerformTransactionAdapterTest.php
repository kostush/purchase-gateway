<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewCardPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use Tests\UnitTestCase;

class NewCardPerformTransactionAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_retrieve_transaction_result_when_valid_data_provided()
    {
        $processTransactionWithBillerAdapter = new NewCardPerformTransactionAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $this->assertInstanceOf(NewCardPerformTransactionAdapter::class, $processTransactionWithBillerAdapter);
    }
}
