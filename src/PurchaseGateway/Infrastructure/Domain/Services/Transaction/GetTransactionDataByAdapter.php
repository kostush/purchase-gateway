<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\RetrieveTransactionDataException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\ApiException;

class GetTransactionDataByAdapter extends BaseTransactionAdapter implements GetTransactionDataByInterfaceAdapter
{
    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return RetrieveTransactionResult
     * @throws RetrieveTransactionDataException
     * @throws Exception
     */
    public function getTransactionDataBy(TransactionId $transactionId, SessionId $sessionId): RetrieveTransactionResult
    {
        try {
            $response = $this->client->getTransactionDataBy((string) $transactionId, (string) $sessionId);

            return $this->translator->translateRetrieveResponse($response, $transactionId);
        } catch (ApiException $e) {
            throw new RetrieveTransactionDataException($e);
        }
    }
}
