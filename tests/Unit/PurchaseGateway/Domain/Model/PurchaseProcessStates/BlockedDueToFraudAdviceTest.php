<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class BlockedDueToFraudAdviceTest extends UnitTestCase
{
    /**
     * @test
     * @return BlockedDueToFraudAdvice|\ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState
     * @throws IllegalStateTransitionException
     */
    public function it_should_return_blocked_due_to_fraud_advice_object()
    {
        $state = BlockedDueToFraudAdvice::create();

        $this->assertInstanceOf(BlockedDueToFraudAdvice::class, $state);

        return $state;
    }

    /**
     * @test
     * @param BlockedDueToFraudAdvice $state CaptchaValidationRequired State
     * @depends it_should_return_blocked_due_to_fraud_advice_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_valid_object_when_validate_method_is_called($state)
    {
        $this->assertInstanceOf(Valid::class, $state->validate());
    }

    /**
     * @test
     * @param BlockedDueToFraudAdvice $state CaptchaValidationRequired State
     * @depends it_should_return_blocked_due_to_fraud_advice_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_requirecaptchavalidation_object_when_block_due_to_fraud_method_is_called($state)
    {
        $this->assertInstanceOf(BlockedDueToFraudAdvice::class, $state->blockDueToFraudAdvice());
    }

    /**
     * @test
     * @param BlockedDueToFraudAdvice $state CaptchaValidationRequired State
     * @depends it_should_return_blocked_due_to_fraud_advice_object
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
     * @param BlockedDueToFraudAdvice $state CaptchaValidationRequired State
     * @depends it_should_return_blocked_due_to_fraud_advice_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_finish_processing_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->finishProcessing();
    }
}
