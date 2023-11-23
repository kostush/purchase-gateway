<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class NetbillingBillerTransactionQueryHttpDTO extends BillerTransactionQueryHttpDTO implements \JsonSerializable
{
    /**
     * NetbillingBillerTransactionQueryHttpDTO constructor.
     * @param RetrieveTransactionResult $transaction transaction result
     * @throws Exception
     * @throws UnknownBillerIdException
     */
    public function __construct(RetrieveTransactionResult $transaction)
    {
        parent::__construct($transaction);

        $this->transaction['billerTransaction']['billerFields']['accountId']        = $transaction->billerFields()
            ->accountId();
        $this->transaction['billerTransaction']['billerFields']['siteTag']          = $transaction->billerFields()
            ->siteTag();
        $this->transaction['billerTransaction']['billerFields']['merchantPassword'] = $transaction->billerFields()
            ->merchantPassword();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->transaction;
    }
}
