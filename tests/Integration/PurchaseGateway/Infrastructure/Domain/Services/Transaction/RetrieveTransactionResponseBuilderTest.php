<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EmptyRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EmptyTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochOtherPaymentTypeRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\OtherPaymentTypeTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResponseBuilder;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCheckRetrieveTransactionResult;
use Tests\IntegrationTestCase;

class RetrieveTransactionResponseBuilderTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_epoch_transaction_when_the_biller_is_epoch(): void
    {
        $transaction = RetrieveTransactionResponseBuilder::build($this->createRetrieveEpochTransaction(), false);
        $this->assertInstanceOf(EpochRetrieveTransactionResult::class, $transaction);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_retrieve_transaction_result(): void
    {
        $transaction = RetrieveTransactionResponseBuilder::build($this->createRetrieveEpochTransaction(), false);
        $this->assertInstanceOf(RetrieveTransactionResult::class, $transaction);
    }

    /**
     * @test
     * @return EmptyRetrieveTransactionResult
     * @throws \Exception
     */
    public function it_should_return_an_empty_retrieve_transaction_result(): EmptyRetrieveTransactionResult
    {
        $transaction = RetrieveTransactionResponseBuilder::build($this->createRetrieveEpochTransaction(''), false);
        $this->assertInstanceOf(EmptyRetrieveTransactionResult::class, $transaction);

        return $transaction;
    }

    /**
     * @test
     * @depends it_should_return_an_empty_retrieve_transaction_result
     * @param EmptyRetrieveTransactionResult $transaction Empty retrieve transaction result
     * @return void
     */
    public function it_should_return_a_retrieve_transaction_result_with_empty_transation_information(EmptyRetrieveTransactionResult $transaction): void
    {
        $this->assertInstanceOf(EmptyTransactionInformation::class, $transaction->transactionInformation());
    }

    /**
     * @test
     * @return EpochOtherPaymentTypeRetrieveTransactionResult Epoch other payment type retrieve transaction result
     * @throws \Exception
     */
    public function it_should_return_a_epoch_other_payment_type_retrieve_transaction_result(): EpochOtherPaymentTypeRetrieveTransactionResult
    {
        $transaction = RetrieveTransactionResponseBuilder::build($this->createRetrieveEpochTransaction(OtherPaymentTypeInfo::PAYMENT_TYPES[0]), false);
        $this->assertInstanceOf(EpochOtherPaymentTypeRetrieveTransactionResult::class, $transaction);

        return $transaction;
    }

    /**
     * @test
     * @depends it_should_return_a_epoch_other_payment_type_retrieve_transaction_result
     * @param EpochOtherPaymentTypeRetrieveTransactionResult $transaction Epoch other payment type retrieve transaction result
     * @return void
     */
    public function it_should_return_other_payment_types_transaction_information(EpochOtherPaymentTypeRetrieveTransactionResult $transaction): void
    {
        $this->assertInstanceOf(OtherPaymentTypeTransactionInformation::class, $transaction->transactionInformation());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_invalid_response_exception(): void
    {
        $this->expectException(InvalidResponseException::class);

        RetrieveTransactionResponseBuilder::build($this->createRetrieveEpochTransaction('nopaymenttype'), false);
    }

    /**
     * @test
     * @return RocketgateCheckRetrieveTransactionResult Rocketgate check payment type retrieve transaction result
     * @throws \Exception
     */
    public function it_should_return_a_rocketgate_check_payment_type_retrieve_transaction_result(): RocketgateCheckRetrieveTransactionResult
    {
        $transaction = RetrieveTransactionResponseBuilder::build($this->createRetrieveRocketgateTransaction(OtherPaymentTypeInfo::PAYMENT_TYPES[1]), false);
        $this->assertInstanceOf(RocketgateCheckRetrieveTransactionResult::class, $transaction);

        return $transaction;
    }

    /**
     * @test
     * @depends it_should_return_a_rocketgate_check_payment_type_retrieve_transaction_result
     * @param RocketgateCheckRetrieveTransactionResult $transaction Rocketgate check payment type retrieve transaction result
     * @return void
     */
    public function it_should_return_check_payment_types_transaction_information(RocketgateCheckRetrieveTransactionResult $transaction): void
    {
        $this->assertInstanceOf(CheckTransactionInformation::class, $transaction->transactionInformation());
    }
}
