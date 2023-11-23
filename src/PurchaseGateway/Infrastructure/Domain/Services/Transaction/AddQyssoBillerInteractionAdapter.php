<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\AddQyssoBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\MalformedPayloadException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoBillerInteraction;
use ProbillerNG\TransactionServiceClient\ApiException as TransactionServiceApiException;
use ProbillerNG\TransactionServiceClient\Model\InlineObject5;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse4001 as TransactionServiceInlineResponse400;

class AddQyssoBillerInteractionAdapter extends BaseTransactionAdapter implements AddQyssoBillerInteractionInterfaceAdapter
{
    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @param array         $returnPayload Return from Qysso payload
     * @return mixed|Transaction
     * @throws TransactionAlreadyProcessedException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function performAddQyssoBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): QyssoBillerInteraction {
        try {
            $response = $this->client->addQyssoBillerInteraction(
                (string) $transactionId,
                (string) $sessionId,
                new InlineObject5(['payload' => json_encode($returnPayload)])
            );

            return $this->translator->translateQyssoBillerInteractionResponse(
                $response,
                (string) $transactionId
            );
        } catch (TransactionServiceApiException $ex) {
            // QYSSO_MALFORMED_PAYLOAD_EXCEPTION when the signature validation failed on qysso service
            /** @var TransactionServiceInlineResponse400 $exceptionResponseObject */
            $exceptionResponseObject = $ex->getResponseObject();
            if (Code::QYSSO_MALFORMED_PAYLOAD_EXCEPTION === $exceptionResponseObject->getCode()
                && $exceptionResponseObject->getError() === Code::getMessage(Code::QYSSO_MALFORMED_PAYLOAD_EXCEPTION)
            ) {
                throw new MalformedPayloadException();
            }

            if ($exceptionResponseObject->getCode() === Code::TRANSACTION_ALREADY_PROCESSED_EXCEPTION) {
                throw new TransactionAlreadyProcessedException();
            }

            // Fallback for unexpected cases
            Log::logException($ex);
            throw $ex;
        } catch (Exception $e) {
            Log::logException($e);
            throw $e;
        }
    }
}
