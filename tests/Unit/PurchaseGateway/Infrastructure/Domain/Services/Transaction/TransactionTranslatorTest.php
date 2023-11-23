<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidTransactionDataResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\TransactionDataNotFoundException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse200;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse404;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use ProbillerNG\TransactionServiceClient\Model\Transaction as ClientTransaction;
use Tests\UnitTestCase;

class TransactionTranslatorTest extends UnitTestCase
{
    public const TRANSACTION_ID = '63c8948c-d09c-11e9-bb65-2a2ae2dbcce4';

    /**
     * @test
     * @return void
     * @throws InvalidTransactionDataResponseException
     * @throws TransactionDataNotFoundException
     */
    public function it_should_throw_an_invalid_transaction_data_response_exception_when_invalid_response_received(): void
    {
        $processTransactionWithBillerTranslator = new TransactionTranslator();
        $this->expectException(InvalidTransactionDataResponseException::class);
        $processTransactionWithBillerTranslator->translateRetrieveResponse(
            '',
            TransactionId::createFromString($this->faker->uuid)
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidTransactionDataResponseException
     * @throws TransactionDataNotFoundException
     */
    public function it_should_throw_an_transaction_data_not_found_exception_when_404_error_received(): void
    {
        $processTransactionWithBillerTranslator = new TransactionTranslator();
        $this->expectException(TransactionDataNotFoundException::class);
        $processTransactionWithBillerTranslator->translateRetrieveResponse(
            new InlineResponse404(),
            TransactionId::createFromString($this->faker->uuid)
        );
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_a_valid_transaction_object(): Transaction
    {
        $response = $this->createMock(ClientTransaction::class);
        $response->method('getTransactionId')->willReturn(self::TRANSACTION_ID);
        $response->method('getStatus')->willReturn(Transaction::STATUS_APPROVED);

        $translator = new TransactionTranslator();

        $result = $translator->translate($response, true, RocketgateBiller::BILLER_NAME);

        $this->assertInstanceOf(Transaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_transaction_object
     * @param Transaction $result Transaction
     * @return void
     */
    public function result_should_have_the_same_state_as_the_transaction_service_response(Transaction $result): void
    {
        $this->assertSame(Transaction::STATUS_APPROVED, $result->state());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_transaction_object
     * @param Transaction $result Transaction
     * @return void
     * @throws \Exception
     */
    public function result_should_have_the_same_transaction_id_as_the_transaction_service_response(Transaction $result): void
    {
        $this->assertSame(self::TRANSACTION_ID, (string) $result->transactionId());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_transaction_object
     * @param Transaction $result Transaction
     * @return void
     * @throws \Exception
     */
    public function result_should_have_the_acs_null_as_default(Transaction $result): void
    {
        $this->assertNull($result->acs());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_transaction_object
     * @param Transaction $result Transaction
     * @return void
     * @throws \Exception
     */
    public function result_should_have_the_pareq_null_as_default(Transaction $result): void
    {
        $this->assertNull($result->pareq());
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_epoch_biller_interaction(): void
    {
        $translator = new TransactionTranslator();
        $result     = $translator->translateEpochBillerInteractionResponse(
            new InlineResponse200(
                [
                    'status'        => 'approved',
                    'paymentType'   => 'cc',
                    'paymentMethod' => 'visa'
                ]
            ),
            self::TRANSACTION_ID
        );

        $this->assertInstanceOf(EpochBillerInteraction::class, $result);
    }

    /**
     * @test
     * @return ThirdPartyTransaction
     * @throws \Exception
     */
    public function it_should_return_a_valid_third_party_transaction_object(): ThirdPartyTransaction
    {
        $response = $this->createMock(ClientTransaction::class);
        $response->method('getTransactionId')->willReturn(self::TRANSACTION_ID);
        $response->method('getStatus')->willReturn(Transaction::STATUS_PENDING);
        $response->method('getRedirectUrl')->willReturn($this->faker->url);

        $translator = new TransactionTranslator();

        $result = $translator->translateThirdPartyResponse($response, RocketgateBiller::BILLER_NAME);

        $this->assertInstanceOf(ThirdPartyTransaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_valid_third_party_transaction_object
     * @param ThirdPartyTransaction $result Transaction
     * @return void
     * @throws \Exception
     */
    public function result_should_have_the_redirect_url_in_the_response(ThirdPartyTransaction $result): void
    {
        $this->assertIsString($result->redirectUrl());
    }

    /**
     * @test
     * @depends it_should_return_a_valid_third_party_transaction_object
     * @param ThirdPartyTransaction $result Transaction
     * @return void
     * @throws \Exception
     */
    public function result_should_have_the_cross_sales_empty_array_as_default(ThirdPartyTransaction $result): void
    {
        $this->assertEmpty($result->crossSales());
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_transaction_object(): Transaction
    {
        $translator = new TransactionTranslator();

        $response = $this->createMock(ClientTransaction::class);
        $response->method('getStatus')->willReturn(Transaction::STATUS_DECLINED);
        $response->method('getCode')->willReturn(TransactionTranslator::IS_NSF_TRANSACTION_CODE);
        $response->method('getTransactionId')->willReturn(self::TRANSACTION_ID);

        $result = $translator->translate($response, true, RocketgateBiller::BILLER_NAME);

        $this->assertInstanceOf(Transaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_transaction_object
     * @param Transaction $result Transaction
     * @return void
     */
    public function result_should_have_isNsf_flag_set_to_true(Transaction $result): void
    {
        $this->assertTrue($result->isNsf());
    }
}
