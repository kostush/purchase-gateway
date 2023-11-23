<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class RetrieveGetTransactionDataByCommand extends ExternalCommand
{
    /** @var GetTransactionDataByInterfaceAdapter */
    private $adapter;

    /** @var TransactionId */
    private $transactionId;

    /** @var SessionId */
    private $sessionId;

    /**
     * RetrieveGetTransactionDataByCommand constructor.
     * @param GetTransactionDataByInterfaceAdapter $adapter       Adapter
     * @param TransactionId                        $transactionId Transaction Id
     * @param SessionId                            $sessionId     Session Id
     */
    public function __construct(
        GetTransactionDataByInterfaceAdapter $adapter,
        TransactionId $transactionId,
        SessionId $sessionId
    ) {
        $this->adapter       = $adapter;
        $this->transactionId = $transactionId;
        $this->sessionId     = $sessionId;
    }

    /**
     * @return RetrieveTransactionResult
     */
    protected function run(): RetrieveTransactionResult
    {
        return $this->adapter->getTransactionDataBy(
            $this->transactionId,
            $this->sessionId
        );
    }
}
