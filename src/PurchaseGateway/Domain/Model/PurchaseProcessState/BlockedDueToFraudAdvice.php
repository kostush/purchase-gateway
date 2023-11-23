<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;

class BlockedDueToFraudAdvice extends AbstractState
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
