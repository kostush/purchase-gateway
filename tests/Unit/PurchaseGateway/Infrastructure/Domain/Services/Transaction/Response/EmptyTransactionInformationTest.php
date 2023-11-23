<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTimeInterface;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EmptyTransactionInformation;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use Tests\UnitTestCase;

class EmptyTransactionInformationTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_empty_transaction_information_object_if_correct_data_is_sent(): void
    {
        $transactionMock = $this->createMock(RetrieveTransactionTransaction::class);
        $transactionMock->method('getTransactionId')->willReturn($this->faker->uuid);
        $transactionMock->method('getAmount')->willReturn('29.99');
        $transactionMock->method('getStatus')->willReturn('aborted');
        $transactionMock->method('getCreatedAt')->willReturn((new \DateTime())->format(DateTimeInterface::ATOM));

        $retrieveTransactionMock = $this->createMock(RetrieveTransaction::class);
        $retrieveTransactionMock->method('getTransaction')->willReturn($transactionMock);
        $retrieveTransactionMock->method('getBillerId')->willReturn(EpochBiller::BILLER_ID);

        $emptyTransactionInformation = new EmptyTransactionInformation($retrieveTransactionMock);

        $this->assertInstanceOf(EmptyTransactionInformation::class, $emptyTransactionInformation);
    }
}
