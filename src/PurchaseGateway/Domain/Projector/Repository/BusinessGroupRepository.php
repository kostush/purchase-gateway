<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\Repository;

use ProBillerNG\Projection\Domain\Repository\ProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;

interface BusinessGroupRepository extends ProjectionRepository
{
    /**
     * @param string $businessGroupId Business group id
     * @return BusinessGroup
     */
    public function findBusinessGroupById(string $businessGroupId): BusinessGroup;
}
