<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerAvailablePaymentMethods;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\State;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use ProBillerNG\PurchaseGateway\Domain\Model\ThreeD;

class NextActionInitFactory implements NextActionFactory
{
    /**
     * @param State                     $state       Purchase State
     * @param Biller                    $biller      Biller
     * @param FraudRecommendation|null  $fraudRecommendation FraudRecommendation
     * @param FraudAdvice|null          $fraudAdvice FraudAdvice
     * @param string|null               $url         Url for redirect
     * @return NextAction
     * @throws InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(
        State $state,
        Biller $biller,
        ?FraudAdvice $fraudAdvice,
        ?FraudRecommendation $fraudRecommendation = null,
        ?string $url = null
    ): NextAction {
        self::assertStateIsValid($state);
        if ($state instanceof BlockedDueToFraudAdvice && $fraudAdvice === null) {
            return RestartProcess::create();
        }

        if ($fraudAdvice && $fraudAdvice->isBlacklistedOnInit()) {
            return RestartProcess::create();
        }

        if ($fraudAdvice && $fraudAdvice->isInitCaptchaAdvised() && !$biller->isThreeDSupported()) {
            return RestartProcess::create();
        }

        if (isset($fraudRecommendation) && $fraudRecommendation->isHardBlock()) {
            return RestartProcess::create();
        }

        return self::createNextActionDependingOfBillerType($biller, $fraudAdvice, $url);
    }

    /**
     * @param Biller           $biller      Biller
     * @param FraudAdvice|null $fraudAdvice FraudAdvice
     * @return RenderGateway
     */
    private static function createRenderGateway(Biller $biller, ?FraudAdvice $fraudAdvice): RenderGateway
    {
        $threeD = null;

        if ($fraudAdvice && $biller->isThreeDSupported()) {
            $threeD = ThreeD::create(
                $fraudAdvice->isForceThreeD(),
                $fraudAdvice->isDetectThreeDUsage()
            );
        }

        return RenderGateway::create($threeD);
    }

    /**
     * @param State $state Sate
     * @throws InvalidStateException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private static function assertStateIsValid(State $state): void
    {
        $validStates = [
            Valid::class,
            Created::class,
            Pending::class,
            BlockedDueToFraudAdvice::class,
        ];
        if (!in_array(get_class($state), $validStates)) {
            throw new InvalidStateException();
        }
    }

    /**
     * @param Biller           $biller      Biller.
     * @param FraudAdvice|null $fraudAdvice Fraud Advice.
     * @param string|null      $url         Url for redirect
     * @return NextAction
     */
    private static function createNextActionDependingOfBillerType(
        Biller $biller,
        ?FraudAdvice $fraudAdvice,
        ?string $url
    ): NextAction {
        if (!$biller->isThirdParty()) {
            return self::createRenderGateway($biller, $fraudAdvice);
        }

        if ($biller instanceof BillerAvailablePaymentMethods) {
            return RenderGatewayOtherPayments::create($biller->availablePaymentMethods());
        }

        $thirdParty = ThirdParty::create($url);

        return RedirectToUrl::create($thirdParty);
    }
}
