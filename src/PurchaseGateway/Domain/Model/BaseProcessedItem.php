<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

abstract class BaseProcessedItem implements ProcessedItem
{
    /**
     * @var ItemId
     */
    protected $itemId;

    /**
     * @var TransactionCollection
     */
    protected $transactionCollection;

    /**
     * @return ItemId
     */
    public function itemId(): ItemId
    {
        return $this->itemId;
    }

    /**
     * @return TransactionCollection
     */
    public function transactionCollection(): TransactionCollection
    {
        return $this->transactionCollection;
    }
}
