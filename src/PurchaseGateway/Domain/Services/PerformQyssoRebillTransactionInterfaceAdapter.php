<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;

interface PerformQyssoRebillTransactionInterfaceAdapter extends TransactionAdapter
{
    /**
     * @param TransactionId $previousTransactionId Previous transaction id
     * @param SessionId     $sessionId             Session id
     * @param array         $rebillPayload         Rebill payload
     * @return ThirdPartyRebillTransaction
     */
    public function performQyssoRebillTransaction(
        TransactionId $previousTransactionId,
        SessionId $sessionId,
        array $rebillPayload
    ): ThirdPartyRebillTransaction;
}
