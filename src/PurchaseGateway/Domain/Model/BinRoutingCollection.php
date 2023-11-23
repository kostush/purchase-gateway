<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class BinRoutingCollection extends Collection
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
        return ($object instanceof BinRouting);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        foreach ($this->getValues() as $object) {
            $data[] = $object->toArray();
        }
        return $data;
    }
}
