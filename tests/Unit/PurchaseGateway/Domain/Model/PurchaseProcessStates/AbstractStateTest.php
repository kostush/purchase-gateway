<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model\PurchaseProcessStates;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Redirected;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use Tests\UnitTestCase;

class AbstractStateTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function restore_should_return_the_correct_object_when_valid_string_is_passed()
    {
        $this->assertInstanceOf(Valid::class, AbstractState::restore('valid'));
    }

    /**
     * @test
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function restore_should_return_the_correct_object_when_created_string_is_passed()
    {
        $this->assertInstanceOf(Created::class, AbstractState::restore('created'));
    }

    /**
     * @test
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function restore_should_return_the_correct_object_when_processed_string_is_passed()
    {
        $this->assertInstanceOf(Processed::class, AbstractState::restore('processed'));
    }

    /**
     * @test
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function restore_should_return_the_correct_object_when_processing_string_is_passed()
    {
        $this->assertInstanceOf(Processing::class, AbstractState::restore('processing'));
    }

    /**
     * @test
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function restore_should_return_the_correct_object_when_blocked_due_to_fraud_advice_string_is_passed()
    {
        $this->assertInstanceOf(BlockedDueToFraudAdvice::class, AbstractState::restore('blockedduetofraudadvice'));
    }

    /**
     * @test
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function restore_should_return_the_correct_object_when_redirected_string_is_passed()
    {
        $this->assertInstanceOf(Redirected::class, AbstractState::restore('redirected'));
    }
}
