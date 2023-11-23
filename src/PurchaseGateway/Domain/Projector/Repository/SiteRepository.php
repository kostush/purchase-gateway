<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\Repository;

use ProBillerNG\Projection\Domain\Repository\ProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

/**
 * @deprecated deprecated since version 1.47.0 . You should not use this anymore.
 * The current usages will be removed along with projector removal.
 *
 * Interface SiteRepository
 * @package ProBillerNG\PurchaseGateway\Domain\Projector\Repository
 */
interface SiteRepository extends ProjectionRepository
{
    /**
     * @deprecated deprecated since version 1.47.0 use the ConfigService::getSite instead.
     * @param string $siteId Site id
     * @return Site|null
     */
    public function findSiteById(string $siteId): ?Site;

    /**
     * @param string $businessGroupId Business group id
     * @return array
     */
    public function findSitesByBusinessGroupId(string $businessGroupId): array;
}
