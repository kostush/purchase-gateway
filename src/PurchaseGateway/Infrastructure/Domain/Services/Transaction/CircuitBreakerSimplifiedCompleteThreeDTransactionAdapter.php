<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\SimplifiedCompleteThreeDInterfaceAdapter;

class CircuitBreakerSimplifiedCompleteThreeDTransactionAdapter extends CircuitBreaker implements SimplifiedCompleteThreeDInterfaceAdapter
{
    /**
     * @var SimplifiedCompleteThreeDTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerSimplifiedCompleteThreeDTransactionAdapter constructor.
     * @param CommandFactory                   $commandFactory Command
     * @param SimplifiedCompleteThreeDTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        SimplifiedCompleteThreeDTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string|null   $queryString   Query string.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     */
    public function performSimplifiedCompleteThreeDTransaction(
        TransactionId $transactionId,
        ?string $queryString,
        SessionId $sessionId
    ): Transaction {
        $command = $this->commandFactory->getCommand(
            SimplifiedCompleteThreeDTransactionCommand::class,
            $this->adapter,
            $transactionId,
            $queryString,
            $sessionId
        );

        return $command->execute();
    }
}
