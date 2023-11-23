<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Repository;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;

interface SiteRepositoryReadOnly
{
    /**
     * @param string $siteId Site id
     * @return Site|null
     */
    public function findSite(string $siteId): ?Site;

    /**
     * @param array $criteria Criteria
     * @return int
     */
    public function countAll(array $criteria = []): int;
}
