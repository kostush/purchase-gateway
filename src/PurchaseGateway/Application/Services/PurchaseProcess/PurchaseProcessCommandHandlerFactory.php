<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\PurchaseGateway\Application\Services\BaseCommandHandler;

interface PurchaseProcessCommandHandlerFactory
{
    /**
     * @param array|null $payment Payment
     * @return BaseCommandHandler
     */
    public function getHandler(?array $payment): BaseCommandHandler;
}
