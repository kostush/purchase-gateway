<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoBillerInteraction;

interface AddQyssoBillerInteractionInterfaceAdapter extends TransactionAdapter
{
    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @param array         $returnPayload Return from Qysso payload
     * @return QyssoBillerInteraction
     */
    public function performAddQyssoBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): QyssoBillerInteraction;
}
