<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\CompleteThreeDInterfaceAdapter;
use ProbillerNG\TransactionServiceClient\Model\CompleteThreeDRequestBody;

class CompleteThreeDTransactionAdapter extends BaseTransactionAdapter implements CompleteThreeDInterfaceAdapter
{
    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string|null   $pares         Pares.
     * @param string|null   $md            Rocketgate biller transaction id.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     * @throws \Exception
     */
    public function performCompleteThreeDTransaction(
        TransactionId $transactionId,
        ?string $pares,
        ?string $md,
        SessionId $sessionId
    ): Transaction {
        try {
            $response = $this->client->performCompleteThreeDTransaction(
                (string) $transactionId,
                $this->getRocketgateCompleteThreeDRequest(
                    $pares,
                    $md
                ),
                (string) $sessionId
            );

            return $this->translator->translate($response, true, RocketgateBiller::BILLER_NAME);

        } catch (\Exception $e) {
            //log api exception
            Log::info('Transaction api exception');
            Log::logException($e);

            return Transaction::create(null, Transaction::STATUS_ABORTED, RocketgateBiller::BILLER_NAME);
        }
    }

    /**
     * @param string|null $pares Pares.
     * @param string|null $md    Rocketgate biller transaction id.
     * @return CompleteThreeDRequestBody Rocketgate complete threeD request.
     */
    private function getRocketgateCompleteThreeDRequest(
        ?string $pares,
        ?string $md
    ): CompleteThreeDRequestBody {

        $completeThreeDRequest = new CompleteThreeDRequestBody();
        $completeThreeDRequest->setPares($pares);
        $completeThreeDRequest->setMd($md);

        return $completeThreeDRequest;
    }
}
