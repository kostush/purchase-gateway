<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\SimplifiedCompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\Model\CompleteSimplifiedThreeDRequestBody;
use ProbillerNG\TransactionServiceClient\Model\Transaction as ClientTransaction;
use Tests\UnitTestCase;

class SimplifiedCompleteThreeDTransactionAdapterTest extends UnitTestCase
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
    private $queryString;

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->transactionServiceClient = $this->createMock(TransactionServiceClient::class);
        $this->transactionTranslator    = $this->createMock(TransactionTranslator::class);
        $this->transactionId            = TransactionId::create();
        $this->queryString              = 'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&invoiceID=aba9b991-61642f82223498.08058272&hash=4qEW12Qdl5%2FYxkCtRbZ%2FHT%2Bi1NM%3D';
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function is_should_call_perform_simplified_complete_threed_on_transaction_service_client(): void
    {
        $simplifiedCompleteThreeDRequest = new CompleteSimplifiedThreeDRequestBody();
        $simplifiedCompleteThreeDRequest->setQueryString($this->queryString);

        $this->transactionServiceClient->expects(
            self::once()
        )->method('performSimplifiedCompleteThreeDTransaction')->with(
            (string) $this->transactionId,
            $simplifiedCompleteThreeDRequest
        );

        $simplifiedCompleteThreeDTransactionAdapter = new SimplifiedCompleteThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $simplifiedCompleteThreeDTransactionAdapter->performSimplifiedCompleteThreeDTransaction(
            $this->transactionId,
            $this->queryString,
            SessionId::create()
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function is_should_call_translate_on_transaction_translator(): void
    {
        $clientTransaction = $this->createMock(ClientTransaction::class);

        $this->transactionServiceClient->method('performSimplifiedCompleteThreeDTransaction')->willReturn($clientTransaction);

        $this->transactionTranslator->expects($this->once())->method('translate')->with(
            $clientTransaction,
            null,
            RocketgateBiller::BILLER_NAME
        );

        $simplifiedCompleteThreeDTransactionAdapter = new SimplifiedCompleteThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $simplifiedCompleteThreeDTransactionAdapter->performSimplifiedCompleteThreeDTransaction(
            $this->transactionId,
            $this->queryString,
            SessionId::create()
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function is_should_return_an_aborted_transaction_when_an_exception_is_thrown(): void
    {
        $this->transactionServiceClient->method('performSimplifiedCompleteThreeDTransaction')->willThrowException(
            new Exception()
        );

        $simplifiedCompleteThreeDTransactionAdapter = new SimplifiedCompleteThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $transaction = $simplifiedCompleteThreeDTransactionAdapter->performSimplifiedCompleteThreeDTransaction(
            $this->transactionId,
            $this->queryString,
            SessionId::create()
        );

        self::assertSame(Transaction::STATUS_ABORTED, $transaction->state());
    }
}
