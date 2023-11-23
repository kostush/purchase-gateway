<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;

class PurchaseBiEventFactory
{
    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     * @return PurchaseEvent
     * @throws LoggerException
     * @throws ValidationException
     */
    public static function createForNewCC(
        PurchaseProcess $purchaseProcess
    ): PurchaseEvent {
        if ($purchaseProcess->isPending()) {
            $biEvent = PurchasePending::createForNewCC($purchaseProcess);
        } else {
            $biEvent = PurchaseProcessed::createForNewCC($purchaseProcess);
        }

        return $biEvent;
    }

    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     * @return PurchaseEvent
     * @throws LoggerException
     * @throws ValidationException
     */
    public static function createForPaymentTemplate(
        PurchaseProcess $purchaseProcess
    ): PurchaseEvent {
        if ($purchaseProcess->isPending()) {
            $biEvent = PurchasePending::createForPaymentTemplate($purchaseProcess);
        } else {
            $biEvent = PurchaseProcessed::createForPaymentTemplate($purchaseProcess);
        }

        return $biEvent;
    }

    /**
     * @param PurchaseProcess $purchaseProcess
     *
     * @return PurchaseEvent
     * @throws \Exception
     */
    public static function createForCheck(
        PurchaseProcess $purchaseProcess
    ): PurchaseEvent {
        if ($purchaseProcess->isPending()) {
            $biEvent = PurchasePending::createForCheck($purchaseProcess);
        } else {
            $biEvent = PurchaseProcessed::createForCheck($purchaseProcess);
        }

        return $biEvent;
    }
}
