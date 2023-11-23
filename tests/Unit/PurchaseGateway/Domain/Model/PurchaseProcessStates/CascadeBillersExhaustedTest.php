<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\CascadeBillersExhausted;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use Tests\UnitTestCase;

class CascadeBillersExhaustedTest extends UnitTestCase
{
    /**
     * @test
     * @return AbstractState
     */
    public function it_should_return_a_cascade_billers_exhausted_object(): AbstractState
    {
        $state = new CascadeBillersExhausted();

        $this->assertInstanceOf(CascadeBillersExhausted::class, $state);

        return $state;
    }

    /**
     * @test
     * @param CascadeBillersExhausted $state Pending State
     * @depends it_should_return_a_cascade_billers_exhausted_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_validate_method_is_called(
        CascadeBillersExhausted $state
    ): void {
        $this->expectException(IllegalStateTransitionException::class);
        $state->validate();
    }

    /**
     * @test
     * @param CascadeBillersExhausted $state Pending State
     * @depends it_should_return_a_cascade_billers_exhausted_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_captcha_validation_required_object(CascadeBillersExhausted $state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->blockDueToFraudAdvice();
    }

    /**
     * @test
     * @param CascadeBillersExhausted $state Pending State
     * @depends it_should_return_a_cascade_billers_exhausted_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_start_processing_method_is_called(
        CascadeBillersExhausted $state
    ): void {
        $this->expectException(IllegalStateTransitionException::class);
        $state->startProcessing();
    }

    /**
     * @test
     * @param CascadeBillersExhausted $state Pending State
     * @depends it_should_return_a_cascade_billers_exhausted_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_processed_state_when_finish_processing_method_is_called(
        CascadeBillersExhausted $state
    ): void {
        $this->assertInstanceOf(CascadeBillersExhausted::class, $state->finishProcessing());
    }
}
