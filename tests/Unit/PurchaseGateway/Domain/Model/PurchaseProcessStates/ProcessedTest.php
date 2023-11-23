<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use Tests\UnitTestCase;

class ProcessedTest extends UnitTestCase
{
    /**
     * @test
     * @return Processed|\ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_processed_object()
    {
        $state = \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed::create();

        $this->assertInstanceOf(Processed::class, $state);

        return $state;
    }


    /**
     * @test
     * @param Processed $state Processed State
     * @depends it_should_return_processed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_start_processing_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->startProcessing();
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed $state Processed State
     * @depends it_should_return_processed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_allow_state_transition_to_same_state($state)
    {
        $newState = $state->finishProcessing();

        $this->assertInstanceOf(Processed::class, $newState);
    }

    /**
     * @test
     * @param Processed $state Processed State
     * @depends it_should_return_processed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_validate_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->validate();
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed $state Processed State
     * @depends it_should_return_processed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_block_due_to_fraud_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->blockDueToFraudAdvice();
    }
}
