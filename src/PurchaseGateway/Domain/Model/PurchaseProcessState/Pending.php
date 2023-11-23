<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;

class Pending extends AbstractState
{
    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public function authenticateThreeD(): AbstractState
    {
        return ThreeDAuthenticated::create();
    }

    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public function performThreeDLookup(): AbstractState
    {
        return ThreeDLookupPerformed::create();
    }

    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public function redirect(): AbstractState
    {
        return Redirected::create();
    }

    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public function finishProcessing(): AbstractState
    {
        return Processed::create();
    }
}
