<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Redirected;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDAuthenticated;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDLookupPerformed;
use Tests\UnitTestCase;

class PendingTest extends UnitTestCase
{
    /**
     * @test
     * @return Pending|AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_pending_object()
    {
        $state = Pending::create();

        $this->assertInstanceOf(Pending::class, $state);

        return $state;
    }

    /**
     * @test
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object($state)
    {
        $this->assertInstanceOf(ThreeDAuthenticated::class, $state->authenticateThreeD());
    }

    /**
     * @test
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object_on_lookup($state)
    {
        $this->assertInstanceOf(ThreeDLookupPerformed::class, $state->performThreeDLookup());
    }

    /**
     * @test
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
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
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_captcha_validation_required_object($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->blockDueToFraudAdvice();
    }

    /**
     * @test
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
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
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_finish_processing_method_is_called($state)
    {
        $this->markTestSkipped('Needs to be investigated why this Exception is expected here');
        $this->expectException(IllegalStateTransitionException::class);
        $state->finishProcessing();
    }

    /**
     * @test
     * @param Pending $state Pending State
     * @depends it_should_return_pending_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_processed_object($state)
    {
        $this->assertInstanceOf(Processed::class, $state->finishProcessing());
    }
}
