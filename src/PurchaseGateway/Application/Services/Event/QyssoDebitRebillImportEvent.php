<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;

class QyssoDebitRebillImportEvent extends QyssoDebitPurchaseImportEvent
{
    /**
     * QyssoDebitRebillImportEvent constructor.
     *
     * @param QyssoRetrieveTransactionResult $retrieveTransactionResult Retrieve result
     * @param PurchaseProcessed              $purchaseProcessedEvent    Purchase event
     *
     * @throws Exception
     */
    public function __construct(
        QyssoRetrieveTransactionResult $retrieveTransactionResult,
        PurchaseProcessed $purchaseProcessedEvent
    ) {
        parent::__construct(
            $retrieveTransactionResult,
            $purchaseProcessedEvent
        );
    }
}
