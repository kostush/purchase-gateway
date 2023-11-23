<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\CascadeBillersExhausted;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Redirected;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class ValidTest extends UnitTestCase
{
    /**
     * @test
     * @return Valid|AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_a_valid_object()
    {
        $state = Valid::create();

        $this->assertInstanceOf(Valid::class, $state);

        return $state;
    }

    /**
     * @test
     * @param Valid $state Valid State
     * @depends it_should_return_a_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_processing_object($state)
    {
        $this->assertInstanceOf(Processing::class, $state->startProcessing());
    }

    /**
     * @test
     * @param Valid $state Valid State
     * @depends it_should_return_a_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_processed_object($state)
    {
        $this->assertInstanceOf(Redirected::class, $state->redirect());
    }

    /**
     * @test
     * @param Valid $state Valid State
     * @depends it_should_return_a_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object_when_block_due_fraud_method_is_called($state)
    {
        $this->assertInstanceOf(BlockedDueToFraudAdvice::class, $state->blockDueToFraudAdvice());
    }

    /**
     * @test
     * @param Valid $state Valid State
     * @depends it_should_return_a_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object_when_no_billers_available_method_is_called($state)
    {
        $this->assertInstanceOf(CascadeBillersExhausted::class, $state->noMoreBillersAvailable());
    }
}
