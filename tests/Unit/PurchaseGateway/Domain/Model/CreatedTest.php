<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class CreatedTest extends UnitTestCase
{
    /**
     * @test
     * @return \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created|\ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_created_object()
    {
        $state = \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created::create();

        $this->assertInstanceOf(\ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created::class, $state);

        return $state;
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_valid_object($state)
    {
        $this->assertInstanceOf(Valid::class, $state->validate());
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_captcha_validation_required_object($state)
    {
        $this->assertInstanceOf(\ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice::class, $state->blockDueToFraudAdvice());
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
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
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_finish_processing_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->finishProcessing();
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_start_pending_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->startPending();
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_authenticate_three_d_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->authenticateThreeD();
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created $state Created State
     * @depends it_should_return_created_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_perform_three_d_lookup_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->performThreeDLookup();
    }
}
