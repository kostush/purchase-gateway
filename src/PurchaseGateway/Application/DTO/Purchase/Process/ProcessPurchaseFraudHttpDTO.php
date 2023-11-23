<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class ProcessPurchaseFraudHttpDTO extends ProcessPurchaseHttpDTO
{
    /**
     * ProcessPurchaseGeneralHttpDTO constructor.
     * @param GenericPurchaseProcess $purchaseProcess GenericPurchaseProcess
     * @param TokenGenerator  $tokenGenerator  Token Generator
     * @param Site            $site            Site
     */
    public function __construct(GenericPurchaseProcess $purchaseProcess, TokenGenerator $tokenGenerator, Site $site)
    {
        parent::__construct($purchaseProcess, $tokenGenerator, $site, null);
    }

    /**
     * @return void
     */
    protected function responseData(): void
    {
        $this->response['sessionId'] = (string) $this->purchaseProcess->sessionId();

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

        $this->response['nextAction'] = RestartProcess::create()->toArray();
    }
}
