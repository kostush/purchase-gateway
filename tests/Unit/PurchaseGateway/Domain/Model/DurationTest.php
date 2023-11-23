<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use Tests\UnitTestCase;

class DurationTest extends UnitTestCase
{
    /**
     * @test
     * @return Duration
     */
    public function it_should_return_a_duration_object(): Duration
    {
        $days = Duration::create(365);
        $this->assertInstanceOf(Duration::class, $days);

        return $days;
    }

    /**
     * @test
     * @depends it_should_return_a_duration_object
     * @param Duration $days The duration object
     * @return void
     */
    public function should_contain_the_correct_value($days): void
    {
        $this->assertEquals(365, $days->days());
    }
}
