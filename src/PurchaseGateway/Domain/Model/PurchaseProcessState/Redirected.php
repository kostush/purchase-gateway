<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;

class Redirected extends AbstractState
{
    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public function finishProcessing(): AbstractState
    {
        return Processed::create();
    }

    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public function validate(): AbstractState
    {
        return Valid::create();
    }
}
