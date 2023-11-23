<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Mgpg;

use ProbillerMGPG\Response as MgpgResponse;
use ProbillerMGPG\SubsequentOperations\Common\NextAction;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;

class MgpgSubsequentOperationResponseService
{
    const CASCADE_BILLER_EXHAUSTED = 'cascadeBillersExhausted';

    const REDIRECT_TO_URL = 'redirectToUrl';

    const BLOCK_DUE_TO_FRAUD = 'BlockedDueToFraudAdvice';

    const VALIDATE_CAPTCHA = 'validateCaptcha';

    const FINISH_PROCESS = 'finishProcess';

    const BLACKLIST = 'Blacklist';

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function cascadeBillersExhausted(NextAction $nextAction): bool
    {
        return isset($nextAction->reason) && $nextAction->reason == self::CASCADE_BILLER_EXHAUSTED;
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isRedirectUrl(NextAction $nextAction): bool
    {
        return $nextAction->type == self::REDIRECT_TO_URL
               && isset($nextAction->thirdParty)
               && isset($nextAction->thirdParty->url);
    }

    /**
     * @param  \ProbillerMGPG\Purchase\Common\NextAction  $nextAction
     *
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
        return isset($nextAction->reason) && $nextAction->reason == self::BLOCK_DUE_TO_FRAUD;
    }

    /**
     * @param MgpgResponse $mgpgResponse
     * @return FraudAdvice
     */
    public function translateFraudAdviceInitStepToNg(MgpgResponse $mgpgResponse): FraudAdvice
    {
        $fraudAdvice = FraudAdvice::create();
        $nextAction  = $mgpgResponse->nextAction;

        if ($this->hasCaptcha($nextAction)) {
            $fraudAdvice->markInitCaptchaAdvised();
        }

        if ($this->isBlacklisted($nextAction)) {
            $fraudAdvice->markBlacklistedOnInit();
        }

        return $fraudAdvice;
    }

    /**
     * @param NextAction $nextAction
     * @return FraudAdvice
     */
    public function translateFraudAdviceProcessStepToNg(NextAction $nextAction): FraudAdvice
    {
        $fraudAdvice = FraudAdvice::create();

        if ($this->hasCaptcha($nextAction)) {
            $fraudAdvice->markProcessCaptchaAdvised();
        }

        if ($this->isBlacklisted($nextAction)) {
            $fraudAdvice->markBlacklistedOnProcess();
        }

        return $fraudAdvice;
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function hasCaptcha(NextAction $nextAction): bool
    {
        return $nextAction->type == self::VALIDATE_CAPTCHA;
    }

    /**
     * @param NextAction $nextAction
     * @return bool
     */
    public function isBlacklisted(NextAction $nextAction): bool
    {
        return $nextAction->type == self::FINISH_PROCESS
               && isset($nextAction->reasonDetails)
               && $nextAction->reasonDetails->message == self::BLACKLIST;
    }
}
