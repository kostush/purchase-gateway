<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use Tests\UnitTestCase;

class BillerMappingTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $siteId = '8e34c94e-135f-4acb-9141-58b3a6e56c74';

    /**
     * @var string
     */
    private $businessGroupId = '6e24c94e-135f-4acb-9141-58b3a6e56c74';

    /**
     * @test
     * @return BillerMapping
     */
    public function it_should_return_an_biller_mapping_object(): BillerMapping
    {
        $billerFields = $this->createMock(BillerFields::class);
        $result = BillerMapping::create(
            SiteId::createFromString($this->siteId),
            BusinessGroupId::createFromString($this->businessGroupId),
            CurrencyCode::USD,
            RocketgateBiller::BILLER_NAME,
            $billerFields

        );
        $this->assertInstanceOf(BillerMapping::class, $result);
        return $result;
    }

    /**
     * @test
     * @param BillerMapping $billerMapping The BillerMapping object
     * @depends it_should_return_an_biller_mapping_object
     * @return void
     */
    public function it_should_have_the_correct_site_id(BillerMapping $billerMapping): void
    {
        $this->assertSame($this->siteId, (string)$billerMapping->siteId());
    }

    /**
     * @test
     * @param BillerMapping $billerMapping The BillerMapping object
     * @depends it_should_return_an_biller_mapping_object
     * @return void
     */
    public function it_should_have_the_correct_businessGroupId(BillerMapping $billerMapping): void
    {
        $this->assertSame($this->businessGroupId, (string)$billerMapping->businessGroupId());
    }

    /**
     * @test
     * @param BillerMapping $billerMapping The BillerMapping object
     * @depends it_should_return_an_biller_mapping_object
     * @return void
     */
    public function it_should_have_the_correct_currencyCode(BillerMapping $billerMapping): void
    {
        $this->assertSame(CurrencyCode::USD, $billerMapping->currencyCode());
    }

    /**
     * @test
     * @param BillerMapping $billerMapping The BillerMapping object
     * @depends it_should_return_an_biller_mapping_object
     * @return void
     */
    public function it_should_have_the_correct_billerName(BillerMapping $billerMapping): void
    {
        $this->assertSame(RocketgateBiller::BILLER_NAME, $billerMapping->billerName());
    }

    /**
     * @test
     * @param BillerMapping $billerMapping The BillerMapping object
     * @depends it_should_return_an_biller_mapping_object
     * @return void
     */
    public function it_should_have_the_correct_billerFields(BillerMapping $billerMapping): void
    {
        $this->assertInstanceOf(BillerFields::class, $billerMapping->billerFields());
    }

}
