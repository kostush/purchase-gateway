<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;

class ProcessPurchaseGiftcardsCompleteHttpDTO extends ProcessPurchaseGeneralHttpDTO
{
    /**
     * @return void
     * @throws \Exception
     */
    protected function responseData(): void
    {
        parent::responseData();

        /** @var InitializedItem $item */
        $item = $this->purchaseProcess->getFirstSuccessfulItem();

        if ($item instanceof InitializedItem) {
            $this->response['success']       = (bool)  $item->wasItemPurchaseSuccessful();
            $this->response['transactionId'] = (string)$item->lastTransactionId();
            $this->response['itemId']        = (string)$item->itemId();
        }
    }
}
