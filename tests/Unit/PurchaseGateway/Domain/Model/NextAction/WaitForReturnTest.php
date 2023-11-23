<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\WaitForReturn;
use Tests\UnitTestCase;

class WaitForReturnTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_the_exact_values(): void
    {
        $expectedResult = [
            'type'   => WaitForReturn::TYPE,
        ];

        $action = WaitForReturn::create();
        $this->assertEquals($expectedResult, $action->toArray());
    }
}
