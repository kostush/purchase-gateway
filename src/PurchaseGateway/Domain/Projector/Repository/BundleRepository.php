<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\Repository;

use ProBillerNG\Projection\Domain\Repository\ProjectionRepository;

interface BundleRepository extends ProjectionRepository
{
    /**
     * @param string $addonId Addon id
     * @return array
     */
    public function findByAddonId(string $addonId): array;
}
