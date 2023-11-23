<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use Tests\UnitTestCase;

class RestartProcessTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_the_exact_values(): void
    {
        $errorMessage   = 'Error message.';
        $expectedResult = [
            'type'  => RestartProcess::TYPE,
            'error' => $errorMessage
        ];

        $action = RestartProcess::create($errorMessage);
        $this->assertEquals($expectedResult, $action->toArray());
    }
}
