<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\CompleteThreeDInterfaceAdapter;

class CircuitBreakerCompleteThreeDTransactionAdapter extends CircuitBreaker implements CompleteThreeDInterfaceAdapter
{
    /**
     * @var CompleteThreeDTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerCompleteThreeDTransactionAdapter constructor.
     * @param CommandFactory                   $commandFactory Command
     * @param CompleteThreeDTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        CompleteThreeDTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string|null   $pares         Pares.
     * @param string|null   $md            Rocketgate biller transaction id.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     */
    public function performCompleteThreeDTransaction(
        TransactionId $transactionId,
        ?string $pares,
        ?string $md,
        SessionId $sessionId
    ): Transaction {
        $command = $this->commandFactory->getCommand(
            CompleteThreeDTransactionCommand::class,
            $this->adapter,
            $transactionId,
            $pares,
            $md,
            $sessionId
        );

        return $command->execute();
    }
}
