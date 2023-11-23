<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NuData;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataAccountInfoData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCard;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCrossSales;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataEnvironmentData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataPurchasedProduct;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataScoreRequestInfo;
use Tests\UnitTestCase;

class NuDataScoreRequestInfoTest extends UnitTestCase
{
    private const VALID_BUSINESS_GROUP_FOR_NU_DATA = '07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1';

    /**
     * @var NuDataScoreRequestInfo
     */
    private $nuDataScoreRequestInfo;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nuDataScoreRequestInfo = new NuDataScoreRequestInfo(
            self::VALID_BUSINESS_GROUP_FOR_NU_DATA,
            $this->createNuDataEnvironmentData(),
            $this->createNuDataPurchasedProduct(),
            $this->createNuDataCard(),
            $this->createNuDataAccountInfoData(),
            $this->createNuDataCrossSales()
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_business_group_id(): void
    {
        $this->assertEquals(self::VALID_BUSINESS_GROUP_FOR_NU_DATA, $this->nuDataScoreRequestInfo->businessGroupId());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_environment_data_object(): void
    {
        $this->assertInstanceOf(NuDataEnvironmentData::class, $this->nuDataScoreRequestInfo->nuDataEnvironmentData());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_purchased_product_object(): void
    {
        $this->assertInstanceOf(NuDataPurchasedProduct::class, $this->nuDataScoreRequestInfo->nuDataPurchasedProduct());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_card_object(): void
    {
        $this->assertInstanceOf(NuDataCard::class, $this->nuDataScoreRequestInfo->nuDataCard());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_account_info_data_object(): void
    {
        $this->assertInstanceOf(NuDataAccountInfoData::class, $this->nuDataScoreRequestInfo->nuDataAccountInfoData());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_cross_sales_object(): void
    {
        $this->assertInstanceOf(NuDataCrossSales::class, $this->nuDataScoreRequestInfo->nuDataCrossSales());
    }
}
