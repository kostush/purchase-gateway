<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

class FraudPurchaseVelocityApproved extends FraudPurchaseVelocity
{
    const TYPE = 'Fraud_Purchase_Velocity_Approved';

    const LATEST_VERSION = 1;

    public static function createFromVelocityEvent(FraudPurchaseVelocity $event)
    {
        $eventAsArray = $event->toArray();
        return (new self(
            $eventAsArray['siteId'],
            $eventAsArray['businessGroupId'],
            $eventAsArray['status'],
            $eventAsArray['memberInfo'],
            $eventAsArray['payment'],
            $eventAsArray['mainPurchaseAmount'],
            $eventAsArray['crossSaleAmount'],
            $eventAsArray['totalChargedAmount'],
            $eventAsArray['countSubmitAttempt'],
            $eventAsArray['billerName']
        ));
    }
}
