<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

class CascadeBillersExhausted extends AbstractState
{
    /**
     * @return AbstractState
     */
    public function finishProcessing(): AbstractState
    {
        return CascadeBillersExhausted::create();
    }
}
