<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

interface BinRoutingService
{
    /**
     * @param PurchaseProcess $purchaseProcess The purchase process manager
     * @param ItemId          $itemId          The item id
     * @param Site            $site            The site
     * @param BillerMapping   $billerMapping   Biller mapping
     * @return BinRoutingCollection
     */
    public function retrieveRoutingCodes(
        PurchaseProcess $purchaseProcess,
        ItemId $itemId,
        Site $site,
        BillerMapping $billerMapping
    ): ?BinRoutingCollection;
}
