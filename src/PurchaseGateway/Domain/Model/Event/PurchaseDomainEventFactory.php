<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Event;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;

/**
 * Class PurchaseDomainEventFactory
 * @package ProBillerNG\PurchaseGateway\Domain\Model\Event
 */
class PurchaseDomainEventFactory
{
    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     * @param Purchase        $purchase        Purchase
     * @return BaseEvent
     * @throws Exception
     */
    public static function create(PurchaseProcess $purchaseProcess, Purchase $purchase): BaseEvent
    {
        return PurchaseProcessed::create($purchaseProcess, $purchase);
    }
}
