<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformQyssoRebillTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;
use ProbillerNG\TransactionServiceClient\Model\AddBillerInteractionForQyssoRebillRequestBody;
use ProbillerNG\TransactionServiceClient\Model\InlineObject5;

class PerformQyssoRebillTransactionAdapter extends BaseTransactionAdapter implements PerformQyssoRebillTransactionInterfaceAdapter
{
    /**
     * @param TransactionId $previousTransactionId Previous transaction Id
     * @param SessionId     $sessionId             Session Id
     * @param array         $rebillPayload         Rebill payload
     * @return mixed|ThirdPartyRebillTransaction
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function performQyssoRebillTransaction(
        TransactionId $previousTransactionId,
        SessionId $sessionId,
        array $rebillPayload
    ): ThirdPartyRebillTransaction {
        try {

            $requestPayload = new AddBillerInteractionForQyssoRebillRequestBody();
            $requestPayload->setPreviousTransactionId((string) $previousTransactionId)
                ->setPayload($rebillPayload);

            $response = $this->client->performQyssoRebillTransaction(
                $requestPayload,
                (string) $sessionId
            );

            return $this->translator->translateQyssoRebillTransactionResponse($response);
        } catch (Exception $e) {
            Log::info('Transaction api exception');
            Log::logException($e);

            throw $e;
        }
    }
}
