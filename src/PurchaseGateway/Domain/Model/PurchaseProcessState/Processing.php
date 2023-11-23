<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception;

class Processing extends AbstractState
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
    public function startPending(): AbstractState
    {
        return Pending::create();
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
    public function finishProcessing(): AbstractState
    {
        return Processed::create();
    }
}
