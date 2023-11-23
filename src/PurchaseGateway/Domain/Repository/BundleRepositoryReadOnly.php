<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Repository;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;

interface BundleRepositoryReadOnly
{
    /**
     * @param string $bundleId Bundle Id
     *
     * @return array
     */
    public function findBundleById(string $bundleId): array;

    /**
     * @param array $bundleIds Bundle Ids
     * @param array $addonIds  Addon Ids
     * @return Bundle[]
     * @throws \Exception
     */
    public function findBundleByIds(array $bundleIds, array $addonIds): array;

    /**
     * @param BundleId $bundleId bundle id
     * @param AddonId  $addonId  addon id
     * @return Bundle|null
     */
    public function findBundleByBundleAddon(BundleId $bundleId, AddonId $addonId): ?Bundle;
}
