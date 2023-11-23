<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface BillerFields
{
    /**
     * @return array
     */
    public function toArray(): array;
}
