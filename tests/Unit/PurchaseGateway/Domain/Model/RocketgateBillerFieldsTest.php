<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use Tests\UnitTestCase;

class RocketgateBillerFieldsTest extends UnitTestCase
{
    /**
     * @test
     * @return RocketgateBillerFields
     */
    public function it_should_return_an_rocketgate_biller_fields_object(): RocketgateBillerFields
    {
        $result = RocketgateBillerFields::create(
            'merchantId',
            'merchantPassword',
            'billerSiteId',
            'sharedSecret',
            true
        );
        $this->assertInstanceOf(RocketgateBillerFields::class, $result);
        return $result;
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_merchant_id(RocketgateBillerFields $billerFields): void
    {
        $this->assertSame('merchantId', $billerFields->merchantId());
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_merchant_password(RocketgateBillerFields $billerFields): void
    {
        $this->assertSame('merchantPassword', $billerFields->merchantPassword());
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_biller_site_id(RocketgateBillerFields $billerFields): void
    {
        $this->assertSame('billerSiteId', $billerFields->billerSiteId());
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_shared_secret(RocketgateBillerFields $billerFields): void
    {
        $this->assertSame('sharedSecret', $billerFields->sharedSecret());
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_simplified_threed_flag(RocketgateBillerFields $billerFields): void
    {
        $this->assertTrue($billerFields->simplified3DS());
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_merchant_customer_id(RocketgateBillerFields $billerFields): void
    {
        $this->assertNull($billerFields->merchantCustomerId());
    }

    /**
     * @test
     * @param RocketgateBillerFields $billerFields RocketgateBillerFields
     * @return void
     * @depends it_should_return_an_rocketgate_biller_fields_object
     */
    public function it_should_have_the_correct_merchant_invoice_id(RocketgateBillerFields $billerFields): void
    {
        $this->assertNull($billerFields->merchantInvoiceId());
    }
}
