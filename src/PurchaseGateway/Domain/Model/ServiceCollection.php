<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class ServiceCollection extends Collection
{
    /**
     * @param mixed $object Object
     * @return bool
     */
    protected function isValidObject($object)
    {
        return $object instanceof Service;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $serviceCollectionArr = [];

        foreach (parent::toArray() as $service) {
            /** @var Service $service */
            $serviceCollectionArr[] = $service->toArray();
        }

        return $serviceCollectionArr;
    }
}
