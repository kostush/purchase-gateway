<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class CircuitBreakerPerformAbortTransactionServiceAdapter extends CircuitBreaker implements PerformAbortTransactionAdapter
{
    /**
     * @var PerformAbortTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerAbortTransactionServiceAdapter constructor.
     * @param CommandFactory                 $commandFactory Command
     * @param PerformAbortTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        PerformAbortTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @return AbortTransactionResult
     */
    public function abortTransaction(TransactionId $transactionId, SessionId $sessionId): AbortTransactionResult
    {
        $command = $this->commandFactory->getCommand(
            AbortTransactionCommand::class,
            $this->adapter,
            $transactionId,
            $sessionId
        );

        return $command->execute();
    }
}
