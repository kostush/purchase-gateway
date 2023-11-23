<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Domain\Projection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BundleAddon;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\AddonRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BundleRepository;

class BundleAddonsProjection extends Projection
{
    /** @var AddonRepository */
    protected $addonRepository;

    /** @var BundleAddonsProjection */
    protected $bundleRepository;

    /**
     * BundleAddonsProjection constructor.
     * @param AddonRepository  $addonRepository  AddonRepository
     * @param BundleRepository $bundleRepository BundleRepository
     */
    public function __construct(AddonRepository $addonRepository, BundleRepository $bundleRepository)
    {
        $this->addonRepository  = $addonRepository;
        $this->bundleRepository = $bundleRepository;
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBundleCreated(BundleAddon $item): void
    {
        Log::info('Persistence process for bundle creation event', $item->toArray());

        $this->createBundles($item);
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function whenBundleUpdated(BundleAddon $item): void
    {
        Log::info('Persistence process for bundle update event', $item->toArray());

        $this->deleteBundles($item);
        $this->createBundles($item);
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBundleDeleted(BundleAddon $item): void
    {
        Log::info('Persistence process for bundle delete event', ['bundleId' => $item->id()]);

        $this->deleteBundles($item);
    }

    /**
     * @param Addon $item Addon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenAddonCreated(Addon $item): void
    {
        Log::info('Persistence process for addon creation event', $item->toArray());
        $this->addonRepository->add($item);
    }

    /**
     * @param Addon $item Addon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenAddonUpdated(Addon $item): void
    {
        $this->addonRepository->update($item);

        /**
         * @var Bundle[]
         */
        $bundles = $this->bundleRepository->findByAddonId($item->id());

        if (count($bundles) == 0 || $bundles[0]->addonType() == $item->type()) {
            return;
        }

        Log::info('Persistence process for addon update event', $item->toArray());

        array_map(
            function (Bundle $bundle) use ($item) {
                $bundle->setAddonType($item->type());

                $this->bundleRepository->update($bundle);
            },
            $bundles
        );
    }

    /**
     * @param Addon $item Addon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenAddonDeleted(Addon $item): void
    {
        Log::info('Persistence process for addon delete event', ['addonId' => $item->id()]);
        $this->addonRepository->delete($item);
    }

    /**
     * Call the repository to delete actually reset the projection
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenProjectionDeleted(): void
    {
        Log::info('Persistence process for deleting projection');
        $this->addonRepository->deleteProjection();
        $this->bundleRepository->deleteProjection();
    }

    /**
     * Call the repository to reset the projection
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenProjectionReset(): void
    {
        Log::info('Persistence process for deleting projection');
        $this->addonRepository->resetProjection();
        $this->bundleRepository->resetProjection();
    }

    /**
     * @param BundleAddon $item Item
     * @return void
     */
    private function deleteBundles(BundleAddon $item): void
    {
        $bundles = $this->bundleRepository->findBundleById($item->id());

        array_map(
            function ($bundle) {
                $this->bundleRepository->delete($bundle);
            },
            $bundles
        );
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     */
    private function createBundles(BundleAddon $item): void
    {
        $addons = $this->addonRepository->findByIds($item->addons());

        array_map(
            function (Addon $addon) use ($item) {
                $bundle = Bundle::create(
                    BundleId::createFromString($item->id()),
                    $item->requireActiveContent(),
                    AddonId::createFromString($addon->id()),
                    AddonType::create($addon->type())
                );

                $this->bundleRepository->add($bundle);
            },
            $addons
        );
    }
}
