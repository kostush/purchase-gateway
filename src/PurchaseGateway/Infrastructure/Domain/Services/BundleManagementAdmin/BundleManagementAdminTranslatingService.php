<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveBundleManagementEventsAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleManagementAdminService as BundleAdminService;

class BundleManagementAdminTranslatingService implements BundleAdminService
{
    /** @var RetrieveBundleManagementEventsAdapter */
    private $adapter;

    /**
     * EmailService constructor.
     * @param RetrieveBundleManagementEventsAdapter $bundleServiceAdapter RetrieveBundleManagementEventsAdapter
     */
    public function __construct(RetrieveBundleManagementEventsAdapter $bundleServiceAdapter)
    {
        $this->adapter = $bundleServiceAdapter;
    }

    /**
     * @param int|null $lastProjectedItemId Last Projected Item Id
     * @param int      $batchSize           Batch Size
     * @return array
     */
    public function retrieveEvents(?int $lastProjectedItemId, int $batchSize): array
    {
        return $this->adapter->retrieveEvents($lastProjectedItemId, $batchSize);
    }
}
