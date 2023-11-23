<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\AddEpochBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;

class CircuitBreakerAddEpochBillerInteractionAdapter extends CircuitBreaker implements AddEpochBillerInteractionInterfaceAdapter
{
    /**
     * @var AddEpochBillerInteractionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerAddEpochBillerInteractionAdapter constructor.
     * @param CommandFactory                   $commandFactory Command
     * @param AddEpochBillerInteractionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        AddEpochBillerInteractionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @param array         $returnPayload Return payload
     * @return EpochBillerInteraction
     */
    public function performAddEpochBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): EpochBillerInteraction {
        $command = $this->commandFactory->getCommand(
            AddEpochBillerInteractionCommand::class,
            $this->adapter,
            $transactionId,
            $sessionId,
            $returnPayload
        );

        return $command->execute();
    }
}
