<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\BundleIdNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidBundleAddonCombinationException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly;

class BundleValidationService
{
    /**
     * @var BundleRepositoryReadOnly
     */
    protected $bundleRepository;

    /**
     * PurchaseService constructor.
     * @param BundleRepositoryReadOnly $bundleRepository Bundle Repository Read Only
     */
    public function __construct(BundleRepositoryReadOnly $bundleRepository)
    {
        $this->bundleRepository = $bundleRepository;
    }

    /**
     * @param InitializedItemCollection $initializedItemCollection Initialized Item Collection
     * @throws InvalidBundleAddonCombinationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws BundleIdNotFoundException
     * @return void
     */
    public function validateBundleAddon(InitializedItemCollection $initializedItemCollection): void
    {
        do {
            $bundles = $this->bundleRepository->findBundleById(
                (string) $initializedItemCollection->current()->bundleId()
            );

            if (empty($bundles[0])) {
                throw new BundleIdNotFoundException(
                    (string) $initializedItemCollection->current()->bundleId()
                );
            }

            if (!$this->isAddonAttachedToBundle($bundles, $initializedItemCollection->current()->addonId())) {
                throw new InvalidBundleAddonCombinationException(
                    (string) $initializedItemCollection->current()->bundleId(),
                    (string) $initializedItemCollection->current()->addonId()
                );
            }

        } while ($initializedItemCollection->next());

        // reset collection position
        $initializedItemCollection->first();
    }

    /**
     * @param array   $bundles Bundles
     * @param AddonId $addon   Addon Id
     * @return bool
     */
    private function isAddonAttachedToBundle(array $bundles, AddonId $addon): bool
    {
        foreach ($bundles as $bundle) {
            /** @var Bundle $bundle */
            if ($bundle->addonId()->equals($addon)) {
                return true;
            }
        }

        return false;
    }
}
