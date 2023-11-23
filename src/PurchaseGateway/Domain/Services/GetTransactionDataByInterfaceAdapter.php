<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

interface GetTransactionDataByInterfaceAdapter extends TransactionAdapter
{
    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @return RetrieveTransactionResult
     */
    public function getTransactionDataBy(TransactionId $transactionId, SessionId $sessionId): RetrieveTransactionResult;
}
