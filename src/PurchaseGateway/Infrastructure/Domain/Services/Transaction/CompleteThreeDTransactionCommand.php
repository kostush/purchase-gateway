<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;

class CompleteThreeDTransactionCommand extends ExternalCommand
{
    /**
     * @var CompleteThreeDTransactionAdapter
     */
    private $adapter;

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var string|null
     */
    private $pares;

    /**
     * @var string|null
     */
    private $md;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * CompleteThreeDTransactionCommand constructor.
     * @param CompleteThreeDTransactionAdapter $adapter       Adapter.
     * @param TransactionId                    $transactionId Transaction id.
     * @param string|null                      $pares         Pares.
     * @param string|null                      $md            Rocketgate biller transaction id.
     * @param SessionId                        $sessionId     Session id.
     */
    public function __construct(
        CompleteThreeDTransactionAdapter $adapter,
        TransactionId $transactionId,
        ?string $pares,
        ?string $md,
        SessionId $sessionId
    ) {
        $this->adapter       = $adapter;
        $this->transactionId = $transactionId;
        $this->pares         = $pares;
        $this->md            = $md;
        $this->sessionId     = $sessionId;
    }

    /**
     * @return Transaction
     * @throws \Exception
     */
    protected function run(): Transaction
    {
        return $this->adapter->performCompleteThreeDTransaction(
            $this->transactionId,
            $this->pares,
            $this->md,
            $this->sessionId
        );
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    protected function getFallback(): void
    {
        Log::error('Error contacting Transaction Service');

        throw new UnableToProcessTransactionException();
    }
}
