<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTimeInterface;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\OtherPaymentTypeTransactionInformation;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use Tests\UnitTestCase;

class OtherPaymentTypeTransactionInformationTest extends UnitTestCase
{
    /**
     * @test
     * @return OtherPaymentTypeTransactionInformation
     * @throws \Exception
     */
    public function it_should_return_an_object_when_created(): OtherPaymentTypeTransactionInformation
    {
        $transaction = $this->createMock(RetrieveTransactionTransaction::class);
        $transaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $transaction->method('getAmount')->willReturn('29.99');
        $transaction->method('getStatus')->willReturn('approved');
        $transaction->method('getCreatedAt')->willReturn((new \DateTime())->format(DateTimeInterface::ATOM));
        $transaction->method('getRebillAmount')->willReturn('12');
        $transaction->method('getRebillFrequency')->willReturn('365');
        $transaction->method('getRebillStart')->willReturn('1');

        $retrieveTransactionResponse = $this->createMock(RetrieveTransaction::class);
        $retrieveTransactionResponse->method('getTransaction')->willReturn($transaction);
        $retrieveTransactionResponse->method('getBillerId')->willReturn(EpochBiller::BILLER_ID);
        $retrieveTransactionResponse->method('getPaymentType')->willReturn('ewallet');

        $otherPaymentTypeTransactionInformation = new OtherPaymentTypeTransactionInformation($retrieveTransactionResponse);

        $this->assertInstanceOf(OtherPaymentTypeTransactionInformation::class, $otherPaymentTypeTransactionInformation);

        return $otherPaymentTypeTransactionInformation;
    }

    /**
     * @test
     * @depends it_should_return_an_object_when_created
     * @param OtherPaymentTypeTransactionInformation $otherPaymentTypeTransactionInformation Transaction information
     * @return void
     */
    public function it_should_have_the_correct_payment_type(
        OtherPaymentTypeTransactionInformation $otherPaymentTypeTransactionInformation
    ): void {
        $this->assertSame('ewallet', $otherPaymentTypeTransactionInformation->paymentType());
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_exception_when_wrong_payment_type_provided(): void
    {
        $this->expectException(InvalidResponseException::class);

        $transaction = $this->createMock(RetrieveTransactionTransaction::class);
        $transaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $transaction->method('getAmount')->willReturn('29.99');
        $transaction->method('getStatus')->willReturn('approved');
        $transaction->method('getCreatedAt')->willReturn((new \DateTime())->format(DateTimeInterface::ATOM));
        $transaction->method('getRebillAmount')->willReturn('12');
        $transaction->method('getRebillFrequency')->willReturn('365');
        $transaction->method('getRebillStart')->willReturn('1');

        $retrieveTransactionResponse = $this->createMock(RetrieveTransaction::class);
        $retrieveTransactionResponse->method('getTransaction')->willReturn($transaction);
        $retrieveTransactionResponse->method('getBillerId')->willReturn(EpochBiller::BILLER_ID);
        $retrieveTransactionResponse->method('getPaymentType')->willReturn('randomStuff');

        $otherPaymentTypeTransactionInformation = new OtherPaymentTypeTransactionInformation($retrieveTransactionResponse);
    }
}
