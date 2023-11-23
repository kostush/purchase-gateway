<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Repository;

interface EventTrackerRepository
{
    /**
     * @param string $type Type
     * @return mixed
     */
    public function findEventTrackerBy(string $type);
}
