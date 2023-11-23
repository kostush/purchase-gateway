<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateBillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateRetrieveTransactionResult;

class RocketgateBillerTransactionQueryHttpDTO extends BillerTransactionQueryHttpDTO implements \JsonSerializable
{
    /**
     * RocketgateBillerTransactionQueryHttpDTO constructor.
     * @param RocketgateRetrieveTransactionResult $transaction Transaction
     */
    public function __construct(RocketgateRetrieveTransactionResult $transaction)
    {
        parent::__construct($transaction);

        /** @var RocketgateBillerTransaction $billerTransaction */
        $billerTransaction = $transaction->billerTransactions()->last();

        if ($billerTransaction) {
            $this->transaction['billerTransaction']['billerTransactionId'] = $billerTransaction->getBillerTransactionId();
        }

        $this->transaction['billerTransaction']['billerFields']['merchantId']       = $transaction->merchantId();
        $this->transaction['billerTransaction']['billerFields']['merchantPassword'] = $transaction->merchantPassword();

        $this->transaction['billerTransaction']['billerFields']['merchantInvoiceId']
            = $billerTransaction ? $billerTransaction->getInvoiceId() : $transaction->invoiceId();

        $this->transaction['billerTransaction']['billerFields']['merchantCustomerId']
            = $billerTransaction ? $billerTransaction->getCustomerId() : $transaction->customerId();
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->transaction;
    }
}
