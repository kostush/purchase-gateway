<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use Tests\UnitTestCase;

class EpochBillerTest extends UnitTestCase
{
    /**
     * @test
     * @return EpochBiller
     */
    public function it_should_create_correct_biller(): EpochBiller
    {
        $biller = new EpochBiller();

        $this->assertInstanceOf(EpochBiller::class, $biller);

        return $biller;
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param EpochBiller $biller Epoch biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_name(EpochBiller $biller): void
    {
        $this->assertSame(EpochBiller::BILLER_NAME, $biller->name());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param EpochBiller $biller Epoch biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_name_if_requested_as_string(EpochBiller $biller): void
    {
        $this->assertSame(EpochBiller::BILLER_NAME, (string) $biller);
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param EpochBiller $biller Epoch biller
     * @return void
     */
    public function it_should_contain_the_correct_biller_id(EpochBiller $biller): void
    {
        $this->assertSame(EpochBiller::BILLER_ID, $biller->id());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param EpochBiller $biller Epoch biller
     * @return void
     */
    public function it_should_return_true_for_third_party(EpochBiller $biller): void
    {
        $this->assertTrue($biller->isThirdParty());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param EpochBiller $biller Epoch biller
     * @return void
     */
    public function it_should_return_false_for_three_d_support(EpochBiller $biller): void
    {
        $this->assertFalse($biller->isThreeDSupported());
    }
}
