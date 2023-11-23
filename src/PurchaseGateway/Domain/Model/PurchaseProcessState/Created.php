<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception;

class Created extends AbstractState
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
    public function blockDueToFraudAdvice(): AbstractState
    {
        return BlockedDueToFraudAdvice::create();
    }
}
