<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class ProcessingTest extends UnitTestCase
{
    /**
     * @test
     * @return Processing|AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_processing_object()
    {
        $state = Processing::create();

        $this->assertInstanceOf(Processing::class, $state);

        return $state;
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing $state Processing State
     * @depends it_should_return_processing_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_pending_object($state)
    {
        $this->assertInstanceOf(Pending::class, $state->startPending());
    }

    /**
     * @test
     * @param Processing $state Processing State
     * @depends it_should_return_processing_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_processing_object($state)
    {
        $this->assertInstanceOf(Processing::class, $state->startProcessing());
    }

    /**
     * @test
     * @param Processing $state Processing State
     * @depends it_should_return_processing_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_processed_object($state)
    {
        $this->assertInstanceOf(Processed::class, $state->finishProcessing());
    }

    /**
     * @test
     * @param Processing $state Processing State
     * @depends it_should_return_processing_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object_when_validate_method_is_called($state)
    {
        $this->assertInstanceOf(Valid::class, $state->validate());
    }

    /**
     * @test
     * @param Processing $state Processing State
     * @depends it_should_return_processing_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_block_due_to_fraud_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->blockDueToFraudAdvice();
    }
}
