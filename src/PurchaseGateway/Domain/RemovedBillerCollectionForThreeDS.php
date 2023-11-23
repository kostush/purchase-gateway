<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

use ProBillerNG\Base\Domain\Collection;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;

class RemovedBillerCollectionForThreeDS extends Collection
{
    /**
     * {@inheritdoc}
     * @param mixed $object object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof Biller;
    }
}