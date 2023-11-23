<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ItemSource;

use ProBillerNG\Projection\Domain\ReadItemsByIntegerByBatch;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonUpdated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleCreated;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleDeleted;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\BundleUpdated;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleManagementAdminService;

class BundleAddonRetriever implements ReadItemsByIntegerByBatch
{
    /**
     * @var BundleManagementAdminService
     * The event stream or endpoint from which you retrieve the events that are going to be projected
     */
    private $bundleManagementAdminService;

    /**
     * BundleAddonRetriever constructor.
     * @param BundleManagementAdminService $service Bundle Admin service
     */
    public function __construct(BundleManagementAdminService $service)
    {
        $this->bundleManagementAdminService = $service;
    }

    /**
     * @param int|null $lastProjectedItemId Last Projected item Id
     * @param int      $batchSize           Bach size
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function nextBatchOfItemsSince(?int $lastProjectedItemId, int $batchSize): array
    {
        $events = $this->bundleManagementAdminService->retrieveEvents($lastProjectedItemId, $batchSize);
        if (!empty($events)) {
            Log::info('Retrieved bundles and addons events', $events);
        }

        $itemsToProject = [];

        foreach ($events as $event) {
            switch ($event['typeName']) {
                case AddonCreated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new AddonCreated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case AddonUpdated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new AddonUpdated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case AddonDeleted::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new AddonDeleted(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case BundleCreated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new BundleCreated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case BundleUpdated::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new BundleUpdated(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
                case BundleDeleted::ORIGINAL_EVENT_NAME:
                    $itemsToProject[] = new BundleDeleted(
                        $event['id'],
                        \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $event['occurredOn']),
                        $event['eventBody']
                    );
                    break;
            }
        }

        return $itemsToProject;
    }
}
