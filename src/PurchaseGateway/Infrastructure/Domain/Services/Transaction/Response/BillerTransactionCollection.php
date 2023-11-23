<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\Base\Domain\Collection;

class BillerTransactionCollection extends Collection
{
    /**
     * Validates the object
     *
     * @param mixed $object object
     *
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return ($object instanceof BillerTransaction);
    }
}
