<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class BillerTransactionQueryHttpDTO
{
    /**
     * @var array
     */
    protected $transaction;

    /**
     * BillerTransactionQueryHttpDTO constructor.
     * @param RetrieveTransactionResult $transaction Transaction
     */
    public function __construct(RetrieveTransactionResult $transaction)
    {
        $this->transaction['transactionId'] = $transaction->transactionInformation()->transactionId();
        $this->transaction['siteId']        = $transaction->siteId();
        $this->transaction['currency']      = $transaction->currency();

        $this->transaction['billerTransaction']['billerTransactionId'] = $transaction->transactId();
        $this->transaction['billerTransaction']['billerId']            = $transaction->billerId();
        $this->transaction['billerTransaction']['billerName']          = $transaction->billerName();
    }
}
