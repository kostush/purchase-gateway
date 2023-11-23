<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\State;

class NextActionCompleteFactory implements NextActionFactory
{
    /**
     * @param State $state Purchase State
     * @return NextAction
     * @throws InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        State $state
    ): NextAction {
        switch ($state) {
            case $state instanceof Processed:
                return FinishProcess::create();

            default:
                throw new InvalidStateException();
        }
    }
}
