<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\AbortTransactionException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProbillerNG\TransactionServiceClient\ApiException;

class AbortTransactionAdapter extends BaseTransactionAdapter implements PerformAbortTransactionAdapter
{
    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return AbortTransactionResult
     * @throws AbortTransactionException
     * @throws \Exception
     */
    public function abortTransaction(TransactionId $transactionId, SessionId $sessionId): AbortTransactionResult
    {
        try {
            $response = $this->client->abortTransaction((string) $transactionId, (string) $sessionId);

            return $this->translator->translateAbortTransaction($response, $transactionId);
        } catch (ApiException $e) {
            throw new AbortTransactionException($e);
        }
    }
}
