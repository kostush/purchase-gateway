<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ItemSource;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Domain\ReadItemsByIntegerByBatch;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BusinessGroupUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\SiteUpdated;
use ProBillerNG\PurchaseGateway\Domain\Services\SiteAdminService;

class BusinessGroupSiteRetriever implements ReadItemsByIntegerByBatch
{

    /**
     * @var SiteAdminService
     */
    private $siteAdminService;

    /**
     * BusinessGroupSiteRetriever constructor.
     * @param SiteAdminService $siteAdminService Site admin service
     */
    public function __construct(SiteAdminService $siteAdminService)
    {
        $this->siteAdminService = $siteAdminService;
    }

    /**
     * @param int|null $lastProjectedItemId Last projected item id
     * @param int      $batchSize           Batch size
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     */
    public function nextBatchOfItemsSince(?int $lastProjectedItemId, int $batchSize): array
    {
        $events = $this->siteAdminService->retrieveEvents($lastProjectedItemId, $batchSize);

        Log::info('My retrieve events from event store', $events);

        $itemsToProject = [];

        foreach ($events as $event) {
            switch ($event['typeName']) {
                case BusinessGroupCreated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new BusinessGroupCreated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case BusinessGroupUpdated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new BusinessGroupUpdated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case BusinessGroupDeleted::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new BusinessGroupDeleted(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case SiteCreated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new SiteCreated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case SiteUpdated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new SiteUpdated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case SiteDeleted::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new SiteDeleted(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
            }
        }

        return $itemsToProject;
    }
}
