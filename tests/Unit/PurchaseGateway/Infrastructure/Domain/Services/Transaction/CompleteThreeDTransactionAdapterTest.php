<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\Model\CompleteThreeDRequestBody;
use ProbillerNG\TransactionServiceClient\Model\Transaction as ClientTransaction;
use Tests\UnitTestCase;

class CompleteThreeDTransactionAdapterTest extends UnitTestCase
{
    /**
     * @var TransactionServiceClient
     */
    private $transactionServiceClient;

    /**
     * @var TransactionTranslator
     */
    private $transactionTranslator;

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var string
     */
    private $pares;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->transactionServiceClient = $this->createMock(TransactionServiceClient::class);
        $this->transactionTranslator    = $this->createMock(TransactionTranslator::class);
        $this->transactionId            = TransactionId::create();
        $this->pares                    = 'SimulatedPARES10001000E00B000';
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function is_should_call_perform_complete_threeD_on_transaction_service_client()
    {
        $completeThreeDRequest = new CompleteThreeDRequestBody();
        $completeThreeDRequest->setPares($this->pares);

        $this->transactionServiceClient->expects($this->once())->method('performCompleteThreeDTransaction')->with(
            (string) $this->transactionId,
            $completeThreeDRequest
        );

        $completeThreeDTransactionAdapter = new CompleteThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $completeThreeDTransactionAdapter->performCompleteThreeDTransaction(
            $this->transactionId,
            $this->pares,
            null,
            SessionId::create()
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function is_should_call_translate_on_transaction_translator()
    {
        $clientTransaction = $this->createMock(ClientTransaction::class);

        $this->transactionServiceClient->method('performCompleteThreeDTransaction')->willReturn($clientTransaction);

        $this->transactionTranslator->expects($this->once())->method('translate')->with(
            $clientTransaction,
            true,
            RocketgateBiller::BILLER_NAME
        );

        $completeThreeDTransactionAdapter = new CompleteThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $completeThreeDTransactionAdapter->performCompleteThreeDTransaction(
            $this->transactionId,
            $this->pares,
            null,
            SessionId::create()
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function is_should_return_an_aborted_transaction_when_an_exception_is_thrown()
    {
        $this->transactionServiceClient->method('performCompleteThreeDTransaction')->willThrowException(
            new \Exception()
        );

        $completeThreeDTransactionAdapter = new CompleteThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $transaction = $completeThreeDTransactionAdapter->performCompleteThreeDTransaction(
            $this->transactionId,
            $this->pares,
            null,
            SessionId::create()
        );

        $this->assertSame(Transaction::STATUS_ABORTED, $transaction->state());
    }
}
