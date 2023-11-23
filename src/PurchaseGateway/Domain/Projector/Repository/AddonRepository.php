<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\Repository;

use ProBillerNG\Projection\Domain\Repository\ProjectionRepository;

interface AddonRepository extends ProjectionRepository
{
    public function findByIds(array $ids): array;
}
