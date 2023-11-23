<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NuData;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataPurchasedProduct;
use Tests\UnitTestCase;

class NuDataPurchasedProductTest extends UnitTestCase
{
    /** @var NuDataPurchasedProduct  */
    private $nuDataPurchasedProduct;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nuDataPurchasedProduct = $this->createNuDataPurchasedProduct();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_price(): void
    {
        $this->assertEquals(10, $this->nuDataPurchasedProduct->price());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_bundle_id(): void
    {
        $this->assertNotEmpty($this->nuDataPurchasedProduct->bundleId());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_purchase_successful(): void
    {
        $this->assertEquals(true, $this->nuDataPurchasedProduct->purchaseSuccessful());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_subscription_id(): void
    {
        $this->assertNotEmpty($this->nuDataPurchasedProduct->subscriptionId());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_is_trial(): void
    {
        $this->assertEquals(true, $this->nuDataPurchasedProduct->isTrial());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_is_recurring(): void
    {
        $this->assertEquals(true, $this->nuDataPurchasedProduct->isRecurring());
    }
}