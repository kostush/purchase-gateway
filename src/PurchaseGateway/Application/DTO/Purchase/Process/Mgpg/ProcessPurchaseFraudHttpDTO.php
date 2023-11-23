<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseFraudHttpDTO as NgFraudDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;

class ProcessPurchaseFraudHttpDTO extends NgFraudDTO
{
    /**
     * @return void
     */
    protected function responseData(): void
    {
        // Adding first before calling process dto because setting keys at a specific index in PHP is bothersome.
        $this->response['mgpgSessionId'] = $this->purchaseProcess->mgpgSessionId();
        $this->response['correlationId'] = $this->purchaseProcess->correlationId();

        parent::responseData();

        if($this->response['fraudAdvice']['blacklist']) {
            $this->response['nextAction'] = FinishProcess::create()->toArray();
        }
    }
}
