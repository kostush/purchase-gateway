<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformLookupThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;

class LookupThreeDTransactionCommand extends ExternalCommand
{
    /** @var PerformLookupThreeDTransactionAdapter */
    private $adapter;

    /** @var TransactionId */
    private $transactionId;

    /** @var SessionId */
    private $sessionId;

    /** @var string */
    private $redirectUrl;

    /** @var string */
    private $deviceFingerprintId;

    /** @var PaymentInfo */
    private $paymentInfo;

    /** @var string */
    private $billerName;
    /**
     * @var bool
     */
    private $isNsfSupported;

    /**
     * LookupTransactionCommand constructor.
     * @param PerformLookupThreeDTransactionAdapter $adapter             Adapter
     * @param TransactionId                         $transactionId       Transaction Id
     * @param PaymentInfo                           $paymentInfo         Payment Info
     * @param string                                $redirectUrl         Redirect Url
     * @param string                                $deviceFingerprintId Device fingerprint id
     * @param string                                $billerName          Biller Name
     * @param SessionId                             $sessionId           Session Id
     * @param bool                                  $isNsfSupported      Is NSF supported
     */
    public function __construct(
        PerformLookupThreeDTransactionAdapter $adapter,
        TransactionId $transactionId,
        PaymentInfo $paymentInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        string $billerName,
        SessionId $sessionId,
        bool $isNsfSupported = false
    ) {
        $this->adapter             = $adapter;
        $this->transactionId       = $transactionId;
        $this->sessionId           = $sessionId;
        $this->deviceFingerprintId = $deviceFingerprintId;
        $this->redirectUrl         = $redirectUrl;
        $this->paymentInfo         = $paymentInfo;
        $this->billerName          = $billerName;
        $this->isNsfSupported      = $isNsfSupported;
    }

    /**
     * @return Transaction
     */
    protected function run(): Transaction
    {
        return $this->adapter->lookupTransaction(
            $this->transactionId,
            $this->paymentInfo,
            $this->redirectUrl,
            $this->deviceFingerprintId,
            $this->billerName,
            $this->sessionId,
            $this->isNsfSupported
        );
    }

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    protected function getFallback(): void
    {
        Log::error('Error contacting Transaction Service');

        throw new UnableToProcessTransactionException();
    }
}
