<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;

class RebillUpdateHttpDto extends ProcessPurchaseHttpDTO
{
    /**
     * @return array
     */
    protected function buildNextAction(): array
    {
        return $this->purchaseProcess->nextAction();
    }

    protected function responseData(): void
    {
        $this->response['mgpgSessionId'] = $this->purchaseProcess->mgpgSessionId();
        $this->response['correlationId'] = $this->purchaseProcess->correlationId();
        $this->response['sessionId']     = (string) $this->purchaseProcess->sessionId();
        $this->response['success']       = $this->purchaseProcess->success();
        $this->response['invoice']       = $this->purchaseProcess->invoice();
        $this->response['nextAction']    = $this->purchaseProcess->nextAction();
    }
}
