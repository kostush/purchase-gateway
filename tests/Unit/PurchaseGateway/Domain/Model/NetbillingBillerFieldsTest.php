<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use Tests\UnitTestCase;

class NetbillingBillerFieldsTest extends UnitTestCase
{
    /**
     * @test
     * @return NetbillingBillerFields
     */
    public function it_should_return_an_netbilling_biller_fields_object(): NetbillingBillerFields
    {
        $result = NetbillingBillerFields::create(
            'accountId',
            'siteTag',
            'binRouting',
            'merchantPassword'
        );
        $this->assertInstanceOf(NetbillingBillerFields::class, $result);
        return $result;
    }

    /**
     * @test
     * @param NetbillingBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_netbilling_biller_fields_object
     */
    public function it_should_have_the_correct_account_id(NetbillingBillerFields $billerFields): void
    {
        $this->assertSame('accountId', $billerFields->accountId());
    }

    /**
     * @test
     * @param NetbillingBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_netbilling_biller_fields_object
     */
    public function it_should_have_the_correct_siteTag(NetbillingBillerFields $billerFields): void
    {
        $this->assertSame('siteTag', $billerFields->siteTag());
    }

    /**
     * @test
     * @param NetbillingBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_netbilling_biller_fields_object
     */
    public function it_should_have_the_correct_binRouting(NetbillingBillerFields $billerFields): void
    {
        $this->assertSame('binRouting', $billerFields->binRouting());
    }

    /**
     * @test
     * @param NetbillingBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_netbilling_biller_fields_object
     */
    public function it_should_have_the_correct_merchantPassword(NetbillingBillerFields $billerFields): void
    {
        $this->assertSame('merchantPassword', $billerFields->merchantPassword());
    }

    /**
     * @test
     * @param NetbillingBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_netbilling_biller_fields_object
     */
    public function it_should_set_disable_fraud_checks(NetbillingBillerFields $billerFields): void
    {
        $this->assertFalse($billerFields->disableFraudChecks());

        $billerFields->setDisableFraudChecks(true);

        $this->assertTrue($billerFields->disableFraudChecks());
    }

}
