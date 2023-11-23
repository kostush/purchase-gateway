<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;

class FraudPurchaseVelocityEventDispatcher
{

    /**
     * @param EventIngestionService $eventIngestionService EventIngestionService
     * @param BILoggerService       $biLoggerService       BILoggerService
     * @param FraudPurchaseVelocity $fraudVelocityEvent    FraudPurchaseVelocity
     * @param PaymentInfo           $paymentInfo           PaymentInfo
     * @return void
     * @throws LoggerException
     */
    public static function dispatchFraudVelocityEvent(
        EventIngestionService $eventIngestionService,
        ?BILoggerService $biLoggerService,
        FraudPurchaseVelocity $fraudVelocityEvent,
        PaymentInfo $paymentInfo
    ): void {
        if ($paymentInfo->paymentType() === ChequePaymentInfo::PAYMENT_TYPE)
        {
            self::dispatchFraudChequeVelocityEvent($eventIngestionService, $fraudVelocityEvent, $biLoggerService);
            return;
        }

        $eventIngestionService->queue($fraudVelocityEvent);
        if ($biLoggerService) {
            $biLoggerService->write($fraudVelocityEvent);
        }
        
        self::dispatchApprovedOrDeclinedEvents($eventIngestionService, $biLoggerService, $fraudVelocityEvent);
    }

    private static function dispatchFraudChequeVelocityEvent(
        EventIngestionService $eventIngestionService,
        FraudPurchaseVelocity $fraudVelocityEvent,
        ?BILoggerService $biLoggerService
    ): void {
        $chequeEvent = FraudChequePurchaseVelocity::createFromVelocityEvent($fraudVelocityEvent);
        $eventIngestionService->queue($chequeEvent);
        if ($biLoggerService) {
            $biLoggerService->write($chequeEvent);
        }
    }

    
    /**
     * @param EventIngestionService $eventIngestionService EventIngestionService
     * @param BILoggerService       $bILoggerService       BILoggerService
     * @param FraudPurchaseVelocity $fraudVelocityEvent    FraudPurchaseVelocity
     * @throws LoggerException
     */
    private static function dispatchApprovedOrDeclinedEvents(
        EventIngestionService $eventIngestionService,
        ?BILoggerService $biLoggerService,
        FraudPurchaseVelocity $fraudVelocityEvent
    ): void {
        if ($fraudVelocityEvent->isApproved()) {
            $approvedEvent = FraudPurchaseVelocityApproved::createFromVelocityEvent($fraudVelocityEvent);
            $eventIngestionService->queue($approvedEvent);
            if ($biLoggerService) {
                $biLoggerService->write($approvedEvent);
            }
            return;
        }
        $declinedEvent = FraudPurchaseVelocityDeclined::createFromVelocityEvent($fraudVelocityEvent);
        $eventIngestionService->queue($declinedEvent);
        if ($biLoggerService) {
            $biLoggerService->write($declinedEvent);
        }
    }

}
