<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;

class RebillUpdateFraudHttpDto extends ProcessPurchaseHttpDTO
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
        $this->response['sessionId']     = (string)$this->purchaseProcess->sessionId();

        $this->response['invoice']    = $this->purchaseProcess->invoice();
        $this->response['nextAction'] = FinishProcess::create()->toArray();
        $this->response['success']    = $this->purchaseProcess->success();

        $fraudAdvice['captcha']   = $this->purchaseProcess->shouldShowCaptcha();
        $fraudAdvice['blacklist'] = $this->purchaseProcess->isBlacklistedOnProcess();

        $this->response['fraudAdvice'] = $fraudAdvice;

        $recommendation = FraudIntegrationMapper::mapFraudAdviceToFraudRecommendation(
            $this->purchaseProcess->fraudAdvice()
        );

        $this->response['fraudRecommendation']           = $recommendation->toArray();
        $this->response['fraudRecommendationCollection'] = [];

        if ($this->purchaseProcess->fraudRecommendationCollection()) {
            $recommendation                                  = $this->purchaseProcess->fraudRecommendationCollection();
            $this->response['fraudRecommendationCollection'] = $recommendation->toArray();
            $this->response['fraudRecommendation']           = $this->purchaseProcess->fraudRecommendation()->toArray();
        }
    }
}
