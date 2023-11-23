<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\Base\Domain\Collection;

class IntegrationEventCollection extends Collection
{
    /**
     * @param mixed $object Object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof IntegrationEvent;
    }
}
