<?php

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface ItemRepositoryReadOnly
{
    /**
     * @param string $itemId Item Id
     * @return ProcessedBundleItem
     */
    public function findById(string $itemId): ?ProcessedBundleItem;
}
