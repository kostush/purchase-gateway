<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NuData;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCrossSales;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataPurchasedProduct;
use Tests\UnitTestCase;

class NuDataCrossSalesTest extends UnitTestCase
{
    /**
     * @test
     * @return NuDataCrossSales
     */
    public function it_should_be_a_instance_of_nu_data_cross_sales(): NuDataCrossSales
    {
        $nuDataCrossSales = new NuDataCrossSales();

        $this->assertInstanceOf(NuDataCrossSales::class, $nuDataCrossSales);

        return $nuDataCrossSales;
    }

    /**
     * @test
     * @param NuDataCrossSales $nuDataCrossSales NuData Cross Sales
     * @depends it_should_be_a_instance_of_nu_data_cross_sales
     * @return void
     */
    public function it_should_contain_the_item_of_type_nu_data_purchased_product(NuDataCrossSales $nuDataCrossSales): void
    {
        $nuDataCrossSales->addProduct($this->createNuDataPurchasedProduct());

        $this->assertInstanceOf(NuDataPurchasedProduct::class, $nuDataCrossSales->productsList()[0]);
    }
}