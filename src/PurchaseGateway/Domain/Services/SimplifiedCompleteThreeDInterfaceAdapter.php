<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

interface SimplifiedCompleteThreeDInterfaceAdapter extends TransactionAdapter
{
    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string        $queryString   Query string.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     */
    public function performSimplifiedCompleteThreeDTransaction(
        TransactionId $transactionId,
        string $queryString,
        SessionId $sessionId
    ): Transaction;
}
