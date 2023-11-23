<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;

class AbortTransactionCommand extends ExternalCommand
{
    /** @var PerformAbortTransactionAdapter */
    private $adapter;

    /** @var TransactionId */
    private $transactionId;

    /** @var SessionId */
    private $sessionId;

    /**
     * AbortTransactionCommand constructor.
     * @param PerformAbortTransactionAdapter $adapter       Adapter
     * @param TransactionId                  $transactionId Transaction Id
     * @param SessionId                      $sessionId     Session Id
     */
    public function __construct(
        PerformAbortTransactionAdapter $adapter,
        TransactionId $transactionId,
        SessionId $sessionId
    ) {
        $this->adapter       = $adapter;
        $this->transactionId = $transactionId;
        $this->sessionId     = $sessionId;
    }

    /**
     * @return AbortTransactionResult
     */
    protected function run(): AbortTransactionResult
    {
        return $this->adapter->abortTransaction(
            $this->transactionId,
            $this->sessionId
        );
    }
}
