<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\BinRoutingTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\InMemoryBinRoutingAdapter;
use Tests\UnitTestCase;

class InMemoryBinRoutingAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_retrieve_correct_bin_routing_collection_when_correct_data_is_provided(): void
    {
        $expectedBinRoutingCollection = new BinRoutingCollection();

        $routingCode1 = BinRouting::create(1, 'routingCode1', 'bank1');
        $routingCode2 = BinRouting::create(2, 'routingCode2', 'bank2');
        $expectedBinRoutingCollection->add($routingCode1);
        $expectedBinRoutingCollection->add($routingCode2);

        $adapter = new InMemoryBinRoutingAdapter();

        $this->assertEquals($expectedBinRoutingCollection, $adapter->retrieve());
    }
}
