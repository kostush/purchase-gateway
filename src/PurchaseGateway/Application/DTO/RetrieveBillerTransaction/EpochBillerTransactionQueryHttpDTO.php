<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class EpochBillerTransactionQueryHttpDTO extends BillerTransactionQueryHttpDTO implements \JsonSerializable
{
    /**
     * EpochBillerTransactionQueryHttpDTO constructor.
     * @param RetrieveTransactionResult $transaction Transaction
     * @throws \Exception
     */
    public function __construct(RetrieveTransactionResult $transaction)
    {
        parent::__construct($transaction);

        $this->transaction['billerTransaction']['billerTransactionId']                   = $transaction->billerTransactionId();
        $this->transaction['billerTransaction']['billerFields']['clientId']              = $transaction->billerFields()->clientId();
        $this->transaction['billerTransaction']['billerFields']['clientKey']             = $transaction->billerFields()->clientKey();
        $this->transaction['billerTransaction']['billerFields']['clientVerificationKey'] = $transaction->billerFields()->clientVerificationKey();
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->transaction;
    }
}
