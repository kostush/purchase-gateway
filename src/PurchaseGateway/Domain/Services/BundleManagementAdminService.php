<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

interface BundleManagementAdminService
{
    /**
     * @param int|null $lastProjectedItemId Last Projected Item Id
     * @param int      $batchSize           Batch Size
     * @return array
     */
    public function retrieveEvents(?int $lastProjectedItemId, int $batchSize): array;
}
