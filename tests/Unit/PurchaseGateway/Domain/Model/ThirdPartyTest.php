<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use Tests\UnitTestCase;

class ThirdPartyTest extends UnitTestCase
{
    /**
     * @test
     * @return ThirdParty
     */
    public function it_should_return_a_third_party_object(): ThirdParty
    {
        $action = ThirdParty::create('redirect-url');
        $this->assertInstanceOf(ThirdParty::class, $action);

        return $action;
    }

    /**
     * @test
     * @depends it_should_return_a_third_party_object
     * @param ThirdParty $thirdParty Third party.
     * @return void
     */
    public function it_should_return_the_correct_url(ThirdParty $thirdParty): void
    {
        $this->assertEquals('redirect-url', $thirdParty->url());
    }
}
