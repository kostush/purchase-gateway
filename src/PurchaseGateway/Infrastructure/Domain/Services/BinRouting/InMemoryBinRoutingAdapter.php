<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;

class InMemoryBinRoutingAdapter implements BinRoutingServiceAdapter
{
    /**
     * @return BinRoutingCollection
     */
    public function retrieve(): BinRoutingCollection
    {
        $collection   = new BinRoutingCollection();
        $routingCode1 = BinRouting::create(1, 'routingCode1', 'bank1');
        $routingCode2 = BinRouting::create(2, 'routingCode2', 'bank2');
        $collection->add($routingCode1);
        $collection->add($routingCode2);

        return $collection;
    }
}
