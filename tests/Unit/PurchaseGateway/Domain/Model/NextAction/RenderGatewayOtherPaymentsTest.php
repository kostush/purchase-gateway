<?php

namespace PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGatewayOtherPayments;
use Tests\UnitTestCase;

class RenderGatewayOtherPaymentsTest extends UnitTestCase
{
    /**
     * @test
     * @return RenderGatewayOtherPayments
     */
    public function it_should_return_a_render_gateway_other_payments_object(): RenderGatewayOtherPayments
    {
        $action = RenderGatewayOtherPayments::create(['paymentMethod1']);

        self::assertInstanceOf(RenderGatewayOtherPayments::class, $action);

        return $action;
    }

    /**
     * @test
     * @depends it_should_return_a_render_gateway_other_payments_object
     * @param RenderGatewayOtherPayments $action
     */
    public function it_should_contain_available_payment_methods(RenderGatewayOtherPayments $action): void
    {
        self::assertContains('paymentMethod1', $action->availablePaymentMethods());
    }
}
