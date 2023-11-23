<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use Tests\UnitTestCase;

class RocketgateBillerTest extends UnitTestCase
{
    /**
     * @test
     * @return RocketgateBiller
     */
    public function it_should_create_correct_biller(): RocketgateBiller
    {
        $biller = new RocketgateBiller();

        $this->assertInstanceOf(RocketgateBiller::class, $biller);

        return $biller;
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param RocketgateBiller $biller Rocketgate biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_name(RocketgateBiller $biller): void
    {
        $this->assertSame(RocketgateBiller::BILLER_NAME, $biller->name());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param RocketgateBiller $biller Rocketgate biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_name_if_requested_as_string(RocketgateBiller $biller): void
    {
        $this->assertSame(RocketgateBiller::BILLER_NAME, (string) $biller);
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param RocketgateBiller $biller Rocketgate biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_id(RocketgateBiller $biller): void
    {
        $this->assertSame(RocketgateBiller::BILLER_ID, $biller->id());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param RocketgateBiller $biller Rocketgate biller
     * @return void
     */
    public function it_should_return_false_for_third_party(RocketgateBiller $biller): void
    {
        $this->assertFalse($biller->isThirdParty());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param RocketgateBiller $biller Rocketgate biller
     * @return void
     */
    public function it_should_return_true_for_three_d_support(RocketgateBiller $biller): void
    {
        $this->assertTrue($biller->isThreeDSupported());
    }
}
