<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;

class AddEpochBillerInteractionCommand extends ExternalCommand
{
    /**
     * @var AddEpochBillerInteractionAdapter
     */
    private $adapter;

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * @var array
     */
    private $payload;

    /**
     * AddEpochBillerInteractionCommand constructor.
     * @param AddEpochBillerInteractionAdapter $adapter       Adapter
     * @param TransactionId                    $transactionId Transaction id
     * @param SessionId                        $sessionId     Session id
     * @param array                            $payload       Payload
     */
    public function __construct(
        AddEpochBillerInteractionAdapter $adapter,
        TransactionId $transactionId,
        SessionId $sessionId,
        array $payload
    ) {
        $this->adapter       = $adapter;
        $this->transactionId = $transactionId;
        $this->sessionId     = $sessionId;
        $this->payload       = $payload;
    }

    /**
     * @return EpochBillerInteraction
     * @throws \Exception
     */
    protected function run(): EpochBillerInteraction
    {
        return $this->adapter->performAddEpochBillerInteraction(
            $this->transactionId,
            $this->sessionId,
            $this->payload
        );
    }
}
