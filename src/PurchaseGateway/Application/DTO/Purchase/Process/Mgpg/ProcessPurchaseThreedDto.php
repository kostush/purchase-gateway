<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

class ProcessPurchaseThreedDto extends ProcessPurchaseHttpDTO
{
    /**
     * @return void
     * @throws \Exception
     */
    protected function responseData(): void
    {
        $this->response['mgpgSessionId'] = $this->purchaseProcess->mgpgSessionId();
        $this->response['correlationId'] = $this->purchaseProcess->correlationId();
        $this->response['sessionId']     = (string) $this->purchaseProcess->sessionId();
        $this->response['success']       = true;

        $this->response['nextAction'] = $this->purchaseProcess->nextAction(
            $this->tokenGenerator(),
            $this->cryptService
        );
    }
}
