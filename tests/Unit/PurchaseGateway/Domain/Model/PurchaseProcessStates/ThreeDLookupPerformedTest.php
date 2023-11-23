<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDLookupPerformed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class ThreeDLookupPerformedTest extends UnitTestCase
{
    /**
     * @test
     * @return ThreeDLookupPerformed|AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_threed_lookup_performed_object()
    {
        $state = ThreeDLookupPerformed::create();

        $this->assertInstanceOf(ThreeDLookupPerformed::class, $state);

        return $state;
    }

    /**
     * @test
     * @param ThreeDLookupPerformed $state ThreeDLookupPerformed State
     * @depends it_should_return_threed_lookup_performed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object($state)
    {
        $this->assertInstanceOf(Processed::class, $state->finishProcessing());
    }

    /**
     * @test
     * @param ThreeDLookupPerformed $state ThreeDLookupPerformed State
     * @depends it_should_return_threed_lookup_performed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_valid_when_validate_method_is_called($state)
    {
        $this->assertInstanceOf(Valid::class, $state->validate());
    }

    /**
     * @test
     * @param ThreeDLookupPerformed $state ThreeDLookupPerformed State
     * @depends it_should_return_threed_lookup_performed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_block_due_to_fraud($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->blockDueToFraudAdvice();
    }

    /**
     * @test
     * @param ThreeDLookupPerformed $state ThreeDLookupPerformed State
     * @depends it_should_return_threed_lookup_performed_object
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
     * @param ThreeDLookupPerformed $state ThreeDLookupPerformed State
     * @depends it_should_return_threed_lookup_performed_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_start_pending_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->startPending();
    }
}
