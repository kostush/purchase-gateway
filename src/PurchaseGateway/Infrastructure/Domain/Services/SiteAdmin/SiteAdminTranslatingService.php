<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveSiteAdminEventsAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\SiteAdminService;

class SiteAdminTranslatingService implements SiteAdminService
{

    /** @var RetrieveSiteAdminEventsAdapter */
    private $adapter;

    /**
     * SiteAdminTranslatingService constructor.
     * @param RetrieveSiteAdminEventsAdapter $adminServiceAdapter RetrieveSiteAdminEventsAdapter
     */
    public function __construct(RetrieveSiteAdminEventsAdapter $adminServiceAdapter)
    {
        $this->adapter = $adminServiceAdapter;
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
