<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformLookupThreeDTransactionAdapter;
use ProbillerNG\TransactionServiceClient\Model\CreditCardInformationWithoutMember;
use ProbillerNG\TransactionServiceClient\Model\CreditCardLookup;
use ProbillerNG\TransactionServiceClient\Model\LookupRequestBody;

class LookupThreeDThreeDTransactionAdapter extends BaseTransactionAdapter implements PerformLookupThreeDTransactionAdapter
{
    /**
     * @param TransactionId $transactionId       Transaction id
     * @param PaymentInfo   $paymentInfo         Payment Info
     * @param string        $redirectUrl         Redirect Url
     * @param string        $deviceFingerprintId Device fingerprint id
     * @param string        $billerName          Biller Name
     * @param SessionId     $sessionId           Session id
     * @param bool          $isNsfSupported      Is NSF supported
     * @return Transaction
     * @throws Exception
     */
    public function lookupTransaction(
        TransactionId $transactionId,
        PaymentInfo $paymentInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        string $billerName,
        SessionId $sessionId,
        bool $isNsfSupported = false
    ): Transaction {
        try {

            $requestBody = $this->generateRequestBody(
                $transactionId,
                $paymentInfo,
                $redirectUrl,
                $deviceFingerprintId,
                $isNsfSupported
            );
            $response    = $this->client->lookupThreedsTransaction($requestBody, $billerName, (string) $sessionId);
            $transaction = $this->translator->translate($response, true, $billerName);
        } catch (Exception $e) {
            Log::info('Transaction api exception');
            Log::logException($e);

            return Transaction::create(
                null,
                Transaction::STATUS_ABORTED,
                $billerName,
                true
            );
        }

        return $transaction;
    }

    /**
     * @param TransactionId $transactionId       Transaction id
     * @param PaymentInfo   $paymentInfo         Payment Info
     * @param string        $redirectUrl         Redirect url
     * @param string        $deviceFingerprintId Device fingerprint id
     * @param bool          $isNsfSupported      Is NSF supported
     * @return LookupRequestBody
     */
    private function generateRequestBody(
        TransactionId $transactionId,
        PaymentInfo $paymentInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        bool $isNsfSupported = false
    ): LookupRequestBody {

        $requestBody = new LookupRequestBody();
        $requestBody->setDeviceFingerprintingId($deviceFingerprintId);
        $requestBody->setRedirectUrl($redirectUrl);
        $requestBody->setIsNsfSupported($isNsfSupported);

        /**
         * @var NewCCPaymentInfo $paymentInfo
         */
        $requestPayment = new CreditCardLookup();
        $requestPayment->setMethod($paymentInfo->paymentType());
        $cardInfo = new CreditCardInformationWithoutMember();
        $cardInfo->setCvv($paymentInfo->cvv())
            ->setNumber($paymentInfo->ccNumber())
            ->setExpirationMonth($paymentInfo->expirationMonth())
            ->setExpirationYear($paymentInfo->expirationYear());
        $requestPayment->setInformation($cardInfo);

        $requestBody->setPayment($requestPayment);
        $requestBody->setPreviousTransactionId((string) $transactionId);

        return $requestBody;
    }
}
