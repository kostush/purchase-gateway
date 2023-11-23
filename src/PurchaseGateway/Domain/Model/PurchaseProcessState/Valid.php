<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception;

class Valid extends AbstractState
{
    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function validate(): AbstractState
    {
        return Valid::create();
    }

    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function startProcessing(): AbstractState
    {
        return Processing::create();
    }

    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function redirect(): AbstractState
    {
        return Redirected::create();
    }

    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function startPending(): AbstractState
    {
        return Pending::create();
    }

    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function blockDueToFraudAdvice(): AbstractState
    {
        return BlockedDueToFraudAdvice::create();
    }

    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function noMoreBillersAvailable(): AbstractState
    {
        return CascadeBillersExhausted::create();
    }
}
