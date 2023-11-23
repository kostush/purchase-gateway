<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTime;
use DateTimeInterface;
use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use Tests\UnitTestCase;

class NewCCTransactionInformationTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $transactionId;

    /**
     * @test
     * @return NewCCTransactionInformation
     * @throws Exception
     */
    public function it_should_return_a_cc_transaction_information_object_if_correct_data_is_sent(): NewCCTransactionInformation
    {
        $this->transactionId = $this->faker->uuid;
        $transactionMock     = $this->createMock(RetrieveTransactionTransaction::class);
        $transactionMock->method('getTransactionId')->willReturn($this->transactionId);
        $transactionMock->method('getAmount')->willReturn('29.99');
        $transactionMock->method('getStatus')->willReturn('approved');
        $transactionMock->method('getCreatedAt')->willReturn((new DateTime())->format(DateTimeInterface::ATOM));
        $transactionMock->method('getRebillAmount')->willReturn('12');
        $transactionMock->method('getRebillStart')->willReturn('1');
        $transactionMock->method('getFirst6')->willReturn('123456');
        $transactionMock->method('getLast4')->willReturn('4444');


        $retrieveTransactionMock = $this->createMock(RetrieveTransaction::class);
        $retrieveTransactionMock->method('getTransaction')->willReturn($transactionMock);
        $retrieveTransactionMock->method('getBillerId')->willReturn(RocketgateBiller::BILLER_ID);

        $ccTransactionInformation = new NewCCTransactionInformation($retrieveTransactionMock);

        self::assertInstanceOf(NewCCTransactionInformation::class, $ccTransactionInformation);

        return $ccTransactionInformation;
    }

    /**
     * @test
     * @param NewCCTransactionInformation $ccTransactionInformation NewCCTransactionInformation
     * @depends it_should_return_a_cc_transaction_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_first_6_digits(NewCCTransactionInformation $ccTransactionInformation): void
    {
        self::assertEquals('123456', $ccTransactionInformation->first6());
    }

    /**
     * @test
     * @param NewCCTransactionInformation $ccTransactionInformation NewCCTransactionInformation
     * @depends it_should_return_a_cc_transaction_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_last_4_digits(NewCCTransactionInformation $ccTransactionInformation): void
    {
        self::assertEquals('4444', $ccTransactionInformation->last4());
    }
}
