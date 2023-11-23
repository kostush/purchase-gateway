<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use Tests\UnitTestCase;

class BillerCollectionTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_have_a_biller(): void
    {
        $billerCollection = BillerCollection::buildBillerCollection([new RocketgateBiller()]);

        $this->assertInstanceOf(Biller::class, $billerCollection->first());
    }
}
