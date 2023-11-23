<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Mgpg;

use ProbillerMGPG\Purchase\Common\NextAction;
use ProbillerMGPG\Response as MgpgResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;


class MgpgResponseService
{
    /**
     * @param MgpgResponse $response
     * @return bool
     */
    public function cascadeBillersExhausted(NextAction $nextAction): bool
    {
        return isset($nextAction->reason) && $nextAction->reason == 'cascadeBillersExhausted';
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isDeviceDetection(NextAction $nextAction): bool
    {
        return $nextAction->type == 'deviceDetection3D' && isset($nextAction->threeD);
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isAuth3Dv2(NextAction $nextAction): bool
    {
        return $nextAction->type == 'authenticate3DS2' && isset($nextAction->threeDS2);
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isRedirectUrl(NextAction $nextAction): bool
    {
        return $nextAction->type == 'redirectToUrl'
               && isset($nextAction->thirdParty)
               && isset($nextAction->thirdParty->url);
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isRenderGateway(NextAction $nextAction): bool
    {
        return $nextAction->type == 'renderGateway';
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function blockedDueToFraudAdvice(NextAction $nextAction): bool
    {
        return isset($nextAction->reason) && $nextAction->reason == 'BlockedDueToFraudAdvice';
    }

    /**
     * @param MgpgResponse $response Response received from MGPG
     * @return FraudAdvice
     */
    public function translateFraudAdviceInitStep(MgpgResponse $response): FraudAdvice
    {
        $fa         = FraudAdvice::create();
        $nextAction = $response->nextAction;

        if ($this->hasCaptcha($nextAction)) {
            $fa->markInitCaptchaAdvised();
        }

        if ($this->isBlacklisted($nextAction)) {
            $fa->markBlacklistedOnInit();
        }

        return $fa;
    }

    /**
     * @param MgpgResponse $response
     */
    public function translateFraudAdviceProcessStep(NextAction $nextAction): FraudAdvice
    {
        $fa         = FraudAdvice::create();

        if ($this->hasCaptcha($nextAction)) {
            $fa->markProcessCaptchaAdvised();
        }

        if ($this->isBlacklisted($nextAction)) {
            $fa->markBlacklistedOnProcess();
        }

        if ($this->isAuth3D($nextAction)) {
            // This can only happen on process, init never does 3D challenges on MGPG.
            $fa->markForceThreeDOnProcess();
        }

        return $fa;
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function hasCaptcha(NextAction $nextAction): bool
    {
        return $nextAction->type == 'validateCaptcha';
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isBlacklisted(NextAction $nextAction): bool
    {
        return $nextAction->type == 'finishProcess'
               && isset($nextAction->reasonDetails)
               && $nextAction->reasonDetails->message == 'Blacklist';
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isAuth3D(NextAction $nextAction): bool
    {
        return $nextAction->type == 'authenticate3D' && isset($nextAction->threeD);
    }
}
