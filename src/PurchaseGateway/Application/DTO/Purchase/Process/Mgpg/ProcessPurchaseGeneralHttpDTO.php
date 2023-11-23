<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO as GeneralProcessDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;

class ProcessPurchaseGeneralHttpDTO extends GeneralProcessDTO
{
    /**
     * @return array
     * @throws Exception
     * @throws InvalidStateException
     */
    protected function buildNextAction(): array
    {
        return $this->purchaseProcess->nextAction();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function responseData(): void
    {
        // Adding first before calling process dto so they show up first.
        if ($mgpgSessionId = $this->purchaseProcess->mgpgSessionId()) {
            $this->response['mgpgSessionId'] = $mgpgSessionId;
        }
        $this->response['correlationId'] = $this->purchaseProcess->correlationId();

        parent::responseData();

        /**
         * On NG we seem flag the purchase as a success even when a transaction has not been approved. On the adaptor
         * we flag this as a failed purchase.
         */
        if ($this->purchaseProcess->hasFailedTransactions()) {
            $this->response['success'] = false;
        }

        // This function will return null in case there is no declined TS, and true in case of NSF false in case not.
        $decliendTransactionAndNsf = $this->purchaseProcess->checkForDeclinedAndNsfTransaction();
        if (!is_null($decliendTransactionAndNsf)) {
            $this->response['isNsf'] = $decliendTransactionAndNsf;
        }

        // MGPG does not provide this information, we strip it from the response since it's never set.
        if (isset($this->response['isUsernamePadded'])) {
           unset($this->response['isUsernamePadded']);
        }
    }
}
