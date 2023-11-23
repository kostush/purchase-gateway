<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;

interface PerformLookupThreeDTransactionAdapter extends TransactionAdapter
{
    /**
     * @param TransactionId $transactionId       Transaction Id
     * @param PaymentInfo   $paymentInfo         Payment Info
     * @param string        $redirectUrl         Redirect Url
     * @param string        $deviceFingerprintId Device fingerprint id
     * @param string        $billerName          Biller Name
     * @param SessionId     $sessionId           Session Id
     * @param bool          $isNsfSupported      Is NSF supported
     * @return Transaction
     */
    public function lookupTransaction(
        TransactionId $transactionId,
        PaymentInfo $paymentInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        string $billerName,
        SessionId $sessionId,
        bool $isNsfSupported = false
    ): Transaction;
}
