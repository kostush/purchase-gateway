<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Domain\DomainProjector;
use ProBillerNG\Projection\Domain\ItemToProject;
use ProBillerNG\Projection\Domain\Event\EventBuilder;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BundleAddon;

class BundleAddonsProjector implements DomainProjector
{
    const WORKER_NAME = 'bundle-addons';

    /** @var BundleAddonsProjection */
    protected $projection;

    /** @var EventBuilder */
    protected $eventBuilder;

    /**
     * BundleAddonsProjector constructor.
     * @param BundleAddonsProjection $projection   Projection
     * @param EventBuilder           $eventBuilder Event Builder
     */
    public function __construct(BundleAddonsProjection $projection, EventBuilder $eventBuilder)
    {
        $this->projection   = $projection;
        $this->eventBuilder = $eventBuilder;
    }

    /**
     * Check if projection is subscribed to given item
     *
     * @param ItemToProject $item Item
     *
     * @return bool
     */
    public function isSubscribedTo(ItemToProject $item): bool
    {
        return in_array(
            $item->originalEventName(),
            [
                AddonCreated::ORIGINAL_EVENT_NAME,
                AddonUpdated::ORIGINAL_EVENT_NAME,
                AddonDeleted::ORIGINAL_EVENT_NAME,
                BundleCreated::ORIGINAL_EVENT_NAME,
                BundleUpdated::ORIGINAL_EVENT_NAME,
                BundleDeleted::ORIGINAL_EVENT_NAME
            ]
        );
    }

    /**
     * @param ItemToProject $item Project item
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     */
    public function projectItem(ItemToProject $item): void
    {
        $projectedItem = $this->eventBuilder->createFromItem($item);

        switch ($item->originalEventName()) {
            case AddonCreated::ORIGINAL_EVENT_NAME:
                /** @var Addon $projectedItem */
                $this->whenAddonCreated($projectedItem);
                break;
            case AddonUpdated::ORIGINAL_EVENT_NAME:
                /** @var Addon $projectedItem */
                $this->whenAddonUpdated($projectedItem);
                break;
            case AddonDeleted::ORIGINAL_EVENT_NAME:
                /** @var Addon $projectedItem */
                $this->whenAddonDeleted($projectedItem);
                break;
            case BundleCreated::ORIGINAL_EVENT_NAME:
                /** @var BundleAddon $projectedItem */
                $this->whenBundleCreated($projectedItem);
                break;
            case BundleUpdated::ORIGINAL_EVENT_NAME:
                /** @var BundleAddon $projectedItem */
                $this->whenBundleUpdated($projectedItem);
                break;
            case BundleDeleted::ORIGINAL_EVENT_NAME:
                /** @var BundleAddon $projectedItem */
                $this->whenBundleDeleted($projectedItem);
                break;
        }
    }

    /**
     * @param Addon $item Addon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenAddonCreated(Addon $item): void
    {
        Log::info('Persistence begin for addon creation event', $item->toArray());
        $this->projection->whenAddonCreated($item);
    }

    /**
     * @param Addon $item Addon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenAddonUpdated(Addon $item): void
    {
        Log::info('Persistence begin for addon update event', $item->toArray());
        $this->projection->whenAddonUpdated($item);
    }

    /**
     * @param Addon $item Addon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenAddonDeleted(Addon $item): void
    {
        Log::info('Persistence begin for addon delete event', ['addonId' => $item->id()]);
        $this->projection->whenAddonDeleted($item);
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBundleCreated(BundleAddon $item): void
    {
        Log::info('Persistence begin for bundle creation event', $item->toArray());
        $this->projection->whenBundleCreated($item);
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBundleUpdated(BundleAddon $item): void
    {
        Log::info('Persistence begin for bundle update event', $item->toArray());
        $this->projection->whenBundleUpdated($item);
    }

    /**
     * @param BundleAddon $item BundleAddon
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBundleDeleted(BundleAddon $item): void
    {
        Log::info('Persistence begin for bundle delete event', ['bundleId' => $item->id()]);
        $this->projection->whenBundleDeleted($item);
    }
    /**
     * Deletes the entire projection
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function deleteProjection(): void
    {
        $this->projection->whenProjectionDeleted();
    }

    /**
     * Resets the projection
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function resetProjection(): void
    {
        $this->projection->whenProjectionReset();
    }
}
