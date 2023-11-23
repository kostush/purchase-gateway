<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Domain\Model\ErrorClassification;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidTransactionDataResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\TransactionDataNotFoundException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResponseBuilder;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;
use ProbillerNG\TransactionServiceClient\Model\BillerInteractionForRebillResponse;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse200;
use ProbillerNG\TransactionServiceClient\Model\AbortTransactionResponse;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse404;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse500;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\Transaction as ClientTransaction;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class TransactionTranslator
{
    public const IS_NSF_TRANSACTION_CODE = 105;

    /**
     * @param mixed  $response   The response from the transaction service
     * @param bool   $newCCUsed  New CC used
     * @param string $billerName Biller name
     *
     * @return Transaction
     * @throws InvalidResponseException
     * @throws LoggerException
     * @throws Exception
     */
    public function translate(
        $response,
        ?bool $newCCUsed,
        string $billerName
    ): Transaction {
        if (!$response instanceof ClientTransaction) {
            throw new InvalidResponseException('Response is not an instance of ClientTransaction');
        }

        $isNsf               = false;
        $errorClassification = null;
        $errorCode           = null;

        if ($response->getStatus() == Transaction::STATUS_DECLINED) {
            $isNsf = $response->getCode() == self::IS_NSF_TRANSACTION_CODE;

            // Not all billers have errorClassification
            if (!empty($response->getErrorClassification())) {
                $errorClassification = new ErrorClassification(
                    $response->getErrorClassification()->getGroupDecline(),
                    $response->getErrorClassification()->getErrorType(),
                    $response->getErrorClassification()->getGroupMessage(),
                    $response->getErrorClassification()->getRecommendedAction()
                );
            }

            if (!empty($response->getCode())) {
                $errorCode = (string) $response->getCode();
            }
        }

        $threeD = $response->getThreeD();
        //TODO: this can be removed when this properties will be completely under threed object
        $acs   = null;
        $pareq = null;

        if (method_exists($response, 'getAcs')) {
            $acs   = $response->getAcs();
            $pareq = $response->getPareq();
        }

        $paymentLinkUrl = null;
        if (!is_null($threeD) && method_exists($threeD, 'getPaymentLinkUrl')) {
            $paymentLinkUrl = $threeD->getPaymentLinkUrl();
        }

        $transaction = Transaction::create(
            TransactionId::createFromString($response->getTransactionId()),
            $response->getStatus(),
            $billerName,
            $newCCUsed,
            !is_null($threeD) ? $threeD->getAcs() : $acs,
            !is_null($threeD) ? $threeD->getPareq() : $pareq,
            $response->getRedirectUrl(),
            $isNsf,
            !is_null($threeD) ? $threeD->getDeviceCollectionUrl() : null,
            !is_null($threeD) ? $threeD->getDeviceCollectionJWT() : null,
            $errorClassification,
            $errorCode,
            $paymentLinkUrl
        );

        // this will be set for the 3DS2 transactions
        if (!is_null($threeD) && !is_null($threeD->getStepUpUrl())) {
            $transaction->setThreeDStepUpUrl($threeD->getStepUpUrl());
            $transaction->setThreeDStepUpJwt($threeD->getStepUpJwt());
            $transaction->setMd($threeD->getMd());
        }

        // we need to make sure we always set the threeD version when we have a 3DS flow
        // on decline we might receive null for version, because of an error(eg invalid currency)
        if (!is_null($threeD) && !is_null($threeD->getVersion())) {
            $transaction->setThreeDVersion((int) $threeD->getVersion());
        }

        return $transaction;
    }

    /**
     * @param InlineResponse404|InlineResponse500|RetrieveTransaction $response      Response
     * @param TransactionId                                           $transactionId Transaction id
     *
     * @return RetrieveTransactionResult
     * @throws TransactionDataNotFoundException
     * @throws InvalidTransactionDataResponseException
     * @throws Exception
     */
    public function translateRetrieveResponse($response, TransactionId $transactionId): RetrieveTransactionResult
    {
        try {
            if ($response instanceof RetrieveTransaction) {
                return RetrieveTransactionResponseBuilder::build(
                    $response,
                    ($response->getTransaction()->getCode() == self::IS_NSF_TRANSACTION_CODE)
                );
            }
        } catch (Exception $e) {
            throw new InvalidTransactionDataResponseException($e);
        }

        if ($response instanceof InlineResponse404) {
            throw new TransactionDataNotFoundException((string) $transactionId);
        }

        throw new InvalidTransactionDataResponseException();
    }

    /**
     * @param mixed         $response      Response
     * @param TransactionId $transactionId Transaction id
     *
     * @return AbortTransactionResult
     * @throws InvalidTransactionDataResponseException
     * @throws TransactionDataNotFoundException
     * @throws LoggerException
     */
    public function translateAbortTransaction($response, TransactionId $transactionId): AbortTransactionResult
    {
        try {
            if ($response instanceof AbortTransactionResponse) {
                return new AbortTransactionResult($response);
            }
        } catch (Exception $e) {
            throw new InvalidTransactionDataResponseException($e);
        }

        if ($response instanceof InlineResponse404) {
            throw new TransactionDataNotFoundException((string) $transactionId);
        }

        throw new InvalidTransactionDataResponseException();
    }

    /**
     * @param InlineResponse200 $response      Response from transaction service
     * @param string            $transactionId Transaction id
     *
     * @return EpochBillerInteraction
     * @throws InvalidResponseException
     * @throws LoggerException
     * @throws Exception
     */
    public function translateEpochBillerInteractionResponse(
        $response,
        $transactionId
    ): EpochBillerInteraction {
        if (!$response instanceof InlineResponse200) {
            throw new InvalidResponseException('Response is not an instance of InlineResponse200');
        }

        return EpochBillerInteraction::create(
            TransactionId::createFromString($transactionId),
            $response->getStatus(),
            $response->getPaymentType(),
            $response->getPaymentMethod()
        );
    }

    /**
     * @param InlineResponse200 $response      Response from transaction service
     * @param string            $transactionId Transaction id
     * @return QyssoBillerInteraction
     * @throws InvalidResponseException
     * @throws LoggerException
     * @throws Exception
     */
    public function translateQyssoBillerInteractionResponse(
        $response,
        $transactionId
    ): QyssoBillerInteraction {
        if (!$response instanceof InlineResponse200) {
            throw new InvalidResponseException('Response is not an instance of InlineResponse200');
        }

        return QyssoBillerInteraction::create(
            TransactionId::createFromString($transactionId),
            $response->getStatus(),
            $response->getPaymentType(),
            $response->getPaymentMethod()
        );
    }

    /**
     * @param BillerInteractionForRebillResponse $response Response from transaction service
     * @return ThirdPartyRebillTransaction
     * @throws InvalidResponseException
     * @throws Exception
     */
    public function translateQyssoRebillTransactionResponse($response): ThirdPartyRebillTransaction
    {
        if (!$response instanceof BillerInteractionForRebillResponse) {
            throw new InvalidResponseException('Response is not an instance of InlineResponse200');
        }

        return ThirdPartyRebillTransaction::create(
            TransactionId::createFromString($response->getTransactionId()),
            $response->getStatus()
        );
    }

    /**
     * @param mixed  $response   Response from transaction service
     * @param string $billerName Transaction id
     * @return ThirdPartyTransaction
     * @throws InvalidResponseException
     * @throws LoggerException
     * @throws Exception
     */
    public function translateThirdPartyResponse(
        $response,
        $billerName
    ): ThirdPartyTransaction {
        if (!$response instanceof ClientTransaction) {
            throw new InvalidResponseException('Response is not an instance of ClientTransaction');
        }

        $crossSales = [];
        if (!empty($response->getCrossSales())) {
            foreach ($response->getCrossSales() as $crossSale) {
                $crossSales[] = Transaction::create(
                    TransactionId::createFromString($crossSale->getTransactionId()),
                    $crossSale->getStatus(),
                    $billerName
                );
            }
        }

        return ThirdPartyTransaction::create(
            TransactionId::createFromString($response->getTransactionId()),
            $response->getStatus(),
            $billerName,
            $response->getRedirectUrl(),
            $crossSales
        );
    }
}
