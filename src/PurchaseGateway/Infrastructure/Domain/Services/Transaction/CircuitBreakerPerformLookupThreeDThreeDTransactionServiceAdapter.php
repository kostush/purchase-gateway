<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformLookupThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class CircuitBreakerPerformLookupThreeDThreeDTransactionServiceAdapter extends CircuitBreaker implements PerformLookupThreeDTransactionAdapter
{
    /**
     * @var PerformLookupThreeDTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerPerformLookupThreeDThreeDTransactionServiceAdapter constructor.
     * @param CommandFactory                        $commandFactory Command
     * @param PerformLookupThreeDTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        PerformLookupThreeDTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

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
    ): Transaction {
        $command = $this->commandFactory->getCommand(
            LookupThreeDTransactionCommand::class,
            $this->adapter,
            $transactionId,
            $paymentInfo,
            $redirectUrl,
            $deviceFingerprintId,
            $billerName,
            $sessionId,
            $isNsfSupported
        );

        return $command->execute();
    }
}
