<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTimeInterface;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use Tests\UnitTestCase;

class CheckTransactionInformationTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $transactionId;

    /**
     * @test
     * @return CheckTransactionInformation
     * @throws \Exception
     */
    public function it_should_return_a_check_transaction_information_object_if_correct_data_is_sent(): CheckTransactionInformation
    {
        $this->transactionId = $this->faker->uuid;
        $transactionMock     = $this->createMock(RetrieveTransactionTransaction::class);
        $transactionMock->method('getTransactionId')->willReturn($this->transactionId);
        $transactionMock->method('getAmount')->willReturn('29.99');
        $transactionMock->method('getStatus')->willReturn('approved');
        $transactionMock->method('getCreatedAt')->willReturn((new \DateTime())->format(DateTimeInterface::ATOM));
        $transactionMock->method('getRebillAmount')->willReturn('12');
        $transactionMock->method('getRebillStart')->willReturn('1');

        $retrieveTransactionMock = $this->createMock(RetrieveTransaction::class);
        $retrieveTransactionMock->method('getTransaction')->willReturn($transactionMock);
        $retrieveTransactionMock->method('getBillerId')->willReturn(RocketgateBiller::BILLER_ID);

        $checkTransactionInformation = new CheckTransactionInformation($retrieveTransactionMock);
        $this->assertInstanceOf(CheckTransactionInformation::class, $checkTransactionInformation);
        return $checkTransactionInformation;
    }

    /**
     * @test
     * @param CheckTransactionInformation $checkTransactionInformation CheckTransactionInformation
     * @depends it_should_return_a_check_transaction_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_valid_proper_type(CheckTransactionInformation $checkTransactionInformation): void
    {
        $this->assertEquals(ChequePaymentInfo::PAYMENT_TYPE, $checkTransactionInformation->paymentType());
    }
}