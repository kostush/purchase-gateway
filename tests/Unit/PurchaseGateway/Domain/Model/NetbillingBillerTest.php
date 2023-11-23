<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use Tests\UnitTestCase;

class NetbillingBillerTest extends UnitTestCase
{
    /**
     * @test
     * @return NetbillingBiller
     */
    public function it_should_create_correct_biller(): NetbillingBiller
    {
        $biller = new NetbillingBiller();

        $this->assertInstanceOf(NetbillingBiller::class, $biller);

        return $biller;
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param NetbillingBiller $biller Netbilling biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_name(NetbillingBiller $biller): void
    {
        $this->assertSame(NetbillingBiller::BILLER_NAME, $biller->name());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param NetbillingBiller $biller Netbilling biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_name_if_requested_as_string(NetbillingBiller $biller): void
    {
        $this->assertSame(NetbillingBiller::BILLER_NAME, (string) $biller);
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param NetbillingBiller $biller Netbilling biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_id(NetbillingBiller $biller): void
    {
        $this->assertSame(NetbillingBiller::BILLER_ID, $biller->id());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param NetbillingBiller $biller Netbilling biller
     * @return void
     */
    public function it_should_return_false_for_third_party(NetbillingBiller $biller): void
    {
        $this->assertFalse($biller->isThirdParty());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param NetbillingBiller $biller Netbilling biller
     * @return void
     */
    public function it_should_return_false_for_three_d_support(NetbillingBiller $biller): void
    {
        $this->assertFalse($biller->isThreeDSupported());
    }
}
