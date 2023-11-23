<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception;

class Processed extends AbstractState
{
    /**
     * @return AbstractState
     * @throws Exception\IllegalStateTransitionException
     */
    public function finishProcessing(): AbstractState
    {
        return Processed::create();
    }
}
