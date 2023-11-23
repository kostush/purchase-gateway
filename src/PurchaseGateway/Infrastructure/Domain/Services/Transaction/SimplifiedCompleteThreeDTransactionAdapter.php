<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\SimplifiedCompleteThreeDInterfaceAdapter;
use ProbillerNG\TransactionServiceClient\Model\CompleteSimplifiedThreeDRequestBody;

class SimplifiedCompleteThreeDTransactionAdapter extends BaseTransactionAdapter implements SimplifiedCompleteThreeDInterfaceAdapter
{
    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string        $queryString   Query string.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function performSimplifiedCompleteThreeDTransaction(
        TransactionId $transactionId,
        string $queryString,
        SessionId $sessionId
    ): Transaction {
        try {
            $response = $this->client->performSimplifiedCompleteThreeDTransaction(
                (string) $transactionId,
                $this->getRocketgateSimplifiedCompleteThreeDRequest($queryString),
                (string) $sessionId
            );

            return $this->translator->translate($response, null, RocketgateBiller::BILLER_NAME);
        } catch (Exception $e) {
            Log::info('Transaction api exception');
            Log::logException($e);

            return Transaction::create(null, Transaction::STATUS_ABORTED, RocketgateBiller::BILLER_NAME);
        }
    }

    /**
     * @param string $queryString Query string.
     * @return CompleteSimplifiedThreeDRequestBody Rocketgate simplified complete threeD request.
     */
    private function getRocketgateSimplifiedCompleteThreeDRequest(
        string $queryString
    ): CompleteSimplifiedThreeDRequestBody {

        $completeThreeDRequest = new CompleteSimplifiedThreeDRequestBody();
        $completeThreeDRequest->setQueryString($queryString);

        return $completeThreeDRequest;
    }
}
