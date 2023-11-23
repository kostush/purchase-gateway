<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use Tests\UnitTestCase;

class FinishProcessTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_the_exact_values(): void
    {
        $expectedResult = [
            'type'   => FinishProcess::TYPE,
        ];

        $action = FinishProcess::create();
        $this->assertEquals($expectedResult, $action->toArray());
    }
}
