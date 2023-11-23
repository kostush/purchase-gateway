<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
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
    public function it_should_return_valid_object()
    {
        $state = Valid::create();

        $this->assertInstanceOf(Valid::class, $state);

        return $state;
    }

    /**
     * @test
     * @param Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_processing_object_when_start_processing_method_is_called($state)
    {
        $this->assertInstanceOf(Processing::class, $state->startProcessing());
    }

    /**
     * @test
     * @param Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_pending_object_when_start_pending_method_is_called($state)
    {
        $this->assertInstanceOf(Pending::class, $state->startPending());
    }

    /**
     * @test
     * @param Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_redirected_object_when_start_redirected_method_is_called($state)
    {
        $this->assertInstanceOf(Redirected::class, $state->redirect());
    }

    /**
     * @test
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_requirecaptchavalidation_object_when_block_due_to_fraud_method_is_called($state)
    {
        $this->assertInstanceOf(BlockedDueToFraudAdvice::class, $state->blockDueToFraudAdvice());
    }

    /**
     * @test
     * @param Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_validate_when_validate_method_is_called($state)
    {
        $this->assertInstanceOf(Valid::class, $state->validate());
    }

    /**
     * @test
     * @param Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_return_pending_when_start_pending_method_is_called($state): void
    {
        $this->assertInstanceOf(Pending::class, $state->startPending());
    }

    /**
     * @test
     * @param Valid $state CaptchaValidationRequired Valid
     * @depends it_should_return_valid_object
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_illegal_state_transition_exception_when_finish_processing_method_is_called($state)
    {
        $this->expectException(IllegalStateTransitionException::class);
        $state->finishProcessing();
    }
}
