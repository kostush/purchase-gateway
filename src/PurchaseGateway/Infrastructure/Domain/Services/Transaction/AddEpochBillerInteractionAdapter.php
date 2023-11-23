<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\AddEpochBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProbillerNG\TransactionServiceClient\Model\InlineObject5;

class AddEpochBillerInteractionAdapter extends BaseTransactionAdapter implements AddEpochBillerInteractionInterfaceAdapter
{
    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @param array         $returnPayload Return from Epoch payload
     * @return mixed|Transaction
     * @throws \ProBillerNG\Logger\Exception
     */
    public function performAddEpochBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): EpochBillerInteraction {
        try {
            $response = $this->client->addEpochBillerInteraction(
                (string) $transactionId,
                (string) $sessionId,
                new InlineObject5(['payload' => $returnPayload])
            );

            return $this->translator->translateEpochBillerInteractionResponse(
                $response,
                (string) $transactionId
            );
        } catch (Exception $e) {
            Log::info('Transaction api exception');
            Log::logException($e);

            throw $e;
        }
    }
}
