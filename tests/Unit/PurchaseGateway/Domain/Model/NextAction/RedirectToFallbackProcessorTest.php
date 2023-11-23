<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RedirectToFallbackProcessor;
use Tests\UnitTestCase;

class RedirectToFallbackProcessorTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_the_exact_values(): void
    {
        $expectedResult = [
            'type'   => RedirectToFallbackProcessor::TYPE,
        ];

        $action = RedirectToFallbackProcessor::create();
        $this->assertEquals($expectedResult, $action->toArray());
    }
}
