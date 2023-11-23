<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface ProcessedItem
{
    /**
     * @return ItemId
     */
    public function itemId(): ItemId;

    /**
     * @return TransactionCollection
     */
    public function transactionCollection(): TransactionCollection;
}
