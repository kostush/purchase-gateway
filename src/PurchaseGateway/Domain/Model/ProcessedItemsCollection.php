<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class ProcessedItemsCollection extends Collection
{
    /**
     * {@inheritdoc}
     * @param mixed $object object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof ProcessedItem;
    }
}
