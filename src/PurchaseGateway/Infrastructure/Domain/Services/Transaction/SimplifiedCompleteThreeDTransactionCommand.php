<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;

class SimplifiedCompleteThreeDTransactionCommand extends ExternalCommand
{
    /**
     * @var SimplifiedCompleteThreeDTransactionAdapter
     */
    private $adapter;

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var string
     */
    private $queryString;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * SimplifiedCompleteThreeDTransactionCommand constructor.
     * @param SimplifiedCompleteThreeDTransactionAdapter $adapter       Adapter.
     * @param TransactionId                              $transactionId Transaction id.
     * @param string                                     $queryString   Query string.
     * @param SessionId                                  $sessionId     Session id.
     */
    public function __construct(
        SimplifiedCompleteThreeDTransactionAdapter $adapter,
        TransactionId $transactionId,
        string $queryString,
        SessionId $sessionId
    ) {
        $this->adapter       = $adapter;
        $this->transactionId = $transactionId;
        $this->queryString   = $queryString;
        $this->sessionId     = $sessionId;
    }

    /**
     * @return Transaction
     * @throws Exception
     */
    protected function run(): Transaction
    {
        return $this->adapter->performSimplifiedCompleteThreeDTransaction(
            $this->transactionId,
            $this->queryString,
            $this->sessionId
        );
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    protected function getFallback(): void
    {
        Log::error('Error contacting Transaction Service');

        throw new UnableToProcessTransactionException();
    }
}
