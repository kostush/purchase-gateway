<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;

interface AddEpochBillerInteractionInterfaceAdapter extends TransactionAdapter
{
    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @param array         $returnPayload Return from Epoch payload
     * @return EpochBillerInteraction
     */
    public function performAddEpochBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): EpochBillerInteraction;
}
