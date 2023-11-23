<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Domain\DomainProjector;
use ProBillerNG\Projection\Domain\Event\EventBuilder;
use ProBillerNG\Projection\Domain\ItemToProject;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroupSite;

class BusinessGroupSitesProjector implements DomainProjector
{
    const WORKER_NAME = 'business-group-sites';

    /** @var BusinessGroupSitesProjection */
    protected $projection;

    /** @var EventBuilder */
    protected $eventBuilder;

    /**
     * BusinessGroupSitesProjector constructor.
     * @param BusinessGroupSitesProjection $projection   Business group sites projection
     * @param EventBuilder                 $eventBuilder Event builder
     */
    public function __construct(BusinessGroupSitesProjection $projection, EventBuilder $eventBuilder)
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
                BusinessGroupCreated::ORIGINAL_EVENT_NAME,
                BusinessGroupUpdated::ORIGINAL_EVENT_NAME,
                BusinessGroupDeleted::ORIGINAL_EVENT_NAME,
                SiteCreated::ORIGINAL_EVENT_NAME,
                SiteUpdated::ORIGINAL_EVENT_NAME,
                SiteDeleted::ORIGINAL_EVENT_NAME
            ]
        );
    }

    /**
     * @param ItemToProject $item Item to project
     * @return void
     * @throws \ProBillerNG\Projection\Domain\Exceptions\CannotRecreateEventException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function projectItem(ItemToProject $item): void
    {
        $projectedItem = $this->eventBuilder->createFromItem($item);

        switch ($item->originalEventName()) {
            case BusinessGroupCreated::ORIGINAL_EVENT_NAME:
                $this->whenBusinessGroupCreated($projectedItem);
                break;
            case BusinessGroupUpdated::ORIGINAL_EVENT_NAME:
                $this->whenBusinessGroupUpdated($projectedItem);
                break;
            case BusinessGroupDeleted::ORIGINAL_EVENT_NAME:
                $this->whenBusinessGroupDeleted($projectedItem);
                break;
            case SiteCreated::ORIGINAL_EVENT_NAME:
                $this->whenSiteCreated($projectedItem);
                break;
            case SiteUpdated::ORIGINAL_EVENT_NAME:
                $this->whenSiteUpdated($projectedItem);
                break;
            case SiteDeleted::ORIGINAL_EVENT_NAME:
                $this->whenSiteDeleted($projectedItem);
                break;
        }
    }

    /**
     * @param BusinessGroup $item Business group
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBusinessGroupCreated(BusinessGroup $item): void
    {
        Log::info('Persistence begin for business group creation event with data', $item->toArray());
        $this->projection->whenBusinessGroupCreated($item);
    }

    /**
     * @param BusinessGroup $item Business Group
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBusinessGroupUpdated(BusinessGroup $item): void
    {
        Log::info('Persistence begin for business group update event with data', $item->toArray());
        $this->projection->whenBusinessGroupUpdated($item);
    }

    /**
     * @param BusinessGroup $item Business Group
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenBusinessGroupDeleted(BusinessGroup $item): void
    {
        Log::info('Persistence begin for business group delete event with data', ['itemId' => $item->id()]);
        $this->projection->whenBusinessGroupDeleted($item);
    }

    /**
     * @param BusinessGroupSite $item Business Group
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenSiteCreated(BusinessGroupSite $item): void
    {
        Log::info('Persistence begin for site creation event with data', $item->toArray());
        $this->projection->whenSiteCreated($item);
    }

    /**
     * @param BusinessGroupSite $item Business Group
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenSiteUpdated(BusinessGroupSite $item): void
    {
        Log::info('Persistence begin for site update event with data', $item->toArray());
        $this->projection->whenSiteUpdated($item);
    }

    /**
     * @param BusinessGroupSite $item Business Group
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function whenSiteDeleted(BusinessGroupSite $item): void
    {
        Log::info('Persistence begin for site delete event with data', ['itemId' => $item->id()]);
        $this->projection->whenSiteDeleted($item);
    }

    /**
     * Deletes the entire projection.
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function deleteProjection(): void
    {
        Log::info('Persistence process for deleting projection');
        $this->projection->whenProjectionDeleted();
    }

    /**
     * Resets the projection.
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function resetProjection(): void
    {
        Log::info('Persistence process for deleting projection');
        $this->projection->whenProjectionReset();
    }
}
