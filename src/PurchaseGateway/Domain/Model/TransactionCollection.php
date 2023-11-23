<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class TransactionCollection extends Collection
{

    /**
     * @return string|null
     */
    public function lastState(): ?string
    {
        return $this->last() ? $this->last()->state() : null;
    }

    /**
     * {@inheritdoc}
     * @param mixed $object object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof Transaction;
    }
}
