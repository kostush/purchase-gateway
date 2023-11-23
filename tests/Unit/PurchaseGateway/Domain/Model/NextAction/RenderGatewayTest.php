<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use Tests\UnitTestCase;

class RenderGatewayTest extends UnitTestCase
{
    /**
     * @test
     * @return RenderGateway
     */
    public function it_should_return_a_render_gateway_object_without_three_d(): RenderGateway
    {
        $action = RenderGateway::create();
        $this->assertInstanceOf(RenderGateway::class, $action);

        return $action;
    }

    /**
     * @test
     * @depends it_should_return_a_render_gateway_object_without_three_d
     * @param RenderGateway $action The action object
     * @return void
     */
    public function it_should_contain_the_exact_values_when_no_three_d_object(RenderGateway $action): void
    {
        $this->assertEquals(['type' => RenderGateway::TYPE], $action->toArray());
    }
}
