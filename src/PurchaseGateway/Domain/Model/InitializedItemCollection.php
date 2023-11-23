<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class InitializedItemCollection extends Collection
{
    /**
     * {@inheritdoc}
     * @param mixed $object object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof InitializedItem;
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

    /**
     * @return InitializedItem
     */
    public function retrieveMainItem(): InitializedItem
    {
        /** @var InitializedItem $item */
        foreach ($this->getValues() as $item) {
            if ($item->isCrossSale()) {
                continue;
            }

            return $item;
        }
    }
}
