<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application;

use ProBillerNG\PurchaseGateway\Domain\Model\Force3dsCodes;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;

/**
 * Class FraudIntegrationMapper
 * @package ProBillerNG\PurchaseGateway\Application
 */
class FraudIntegrationMapper
{
    public const BLACKLIST_REQUIRED = 'Blacklist_Customer';
    public const CAPTCHA_REQUIRED   = 'Show_Captcha';
    public const FORCE_THREE_D      = 'Force_3DS';

    public const BLOCK = 'Block';

    /**
     * @param FraudAdvice $fraudAdvice Fraud Advice
     * @return FraudRecommendation
     */
    public static function mapFraudAdviceToFraudRecommendation(FraudAdvice $fraudAdvice): FraudRecommendation
    {
        if ($fraudAdvice->isBlacklistedOnInit() || $fraudAdvice->isBlacklistedOnProcess()) {
            return FraudRecommendation::create(
                FraudRecommendation::BLACKLIST,
                self::BLOCK,
                self::BLACKLIST_REQUIRED
            );
        }

        if ($fraudAdvice->isInitCaptchaAdvised() || $fraudAdvice->isProcessCaptchaAdvised()) {
            return FraudRecommendation::create(
                FraudRecommendation::CAPTCHA,
                self::BLOCK,
                self::CAPTCHA_REQUIRED
            );
        }

        if ($fraudAdvice->isForceThreeD()) {
            return FraudRecommendation::create(
                FraudRecommendation::FORCE_THREE_D,
                self::BLOCK,
                self::FORCE_THREE_D
            );
        }

        return FraudRecommendation::createDefaultAdvice();
    }

    /**
     * @param FraudRecommendationCollection $fraudRecommendationCollection
     * @return FraudAdvice
     */
    public static function mapFraudRecommendationToFraudAdviceOnInit(
        FraudRecommendationCollection $fraudRecommendationCollection
    ): FraudAdvice {
        $advice = FraudAdvice::create();

        foreach ($fraudRecommendationCollection->getIterator() as $fraudRecommendation) {
            if ($fraudRecommendation->code() === FraudRecommendation::CAPTCHA) {
                $advice->markInitCaptchaAdvised();
            }

            if ($fraudRecommendation->code() === FraudRecommendation::BLACKLIST) {
                $advice->markBlacklistedOnInit();
            }

            if (Force3dsCodes::isValid($fraudRecommendation->code())) {
                $advice->markForceThreeDOnInit();
            }
        }

        return $advice;
    }

    /**
     * @param FraudRecommendationCollection $fraudRecommendationCollection
     * @param null|FraudAdvice              $previousAdvice
     * @return FraudAdvice
     */
    public static function mapFraudRecommendationToFraudAdviceOnProcess(
        FraudRecommendationCollection $fraudRecommendationCollection,
        ?FraudAdvice $previousAdvice
    ): FraudAdvice {
        $advice = FraudAdvice::createFromPreviousAdviceOnProcess($previousAdvice ?? FraudAdvice::create());

        foreach ($fraudRecommendationCollection->getIterator() as $fraudRecommendation) {
            if ($fraudRecommendation->code() === FraudRecommendation::CAPTCHA) {
                $advice->markProcessCaptchaAdvised();
            }

            if ($fraudRecommendation->code() === FraudRecommendation::BLACKLIST) {
                $advice->markBlacklistedOnProcess();
            }

            if (Force3dsCodes::isValid($fraudRecommendation->code())) {
                $advice->markForceThreeDOnProcess();
            }
        }

        return $advice;
    }

    /**
     * @deprecated Used on session version10
     * @param array $fraudAdviceArray Fraud advice array
     * @return array
     */
    public static function mapFraudAdviceArrayToFraudRecommendationArray(array $fraudAdviceArray): array
    {
        if ($fraudAdviceArray['blacklistedOnInit'] || $fraudAdviceArray['blacklistedOnProcess']) {
            return self::fraudRecommendationBlacklistArray();
        }

        if ($fraudAdviceArray['initCaptchaAdvised'] || $fraudAdviceArray['processCaptchaAdvised']) {
            return self::fraudRecommendationCaptchaArray();
        }

        if ($fraudAdviceArray['forceThreeD']) {
            return self::fraudRecommendationForceThreeDArray();
        }

        return FraudRecommendation::createDefaultAdvice()->toArray();
    }

    /**
     * @return array
     */
    public static function fraudRecommendationDefaultArray(): array
    {
        return [
            'severity' => FraudRecommendation::DEFAULT_SEVERITY,
            'code'     => FraudRecommendation::DEFAULT_CODE,
            'message'  => FraudRecommendation::DEFAULT_MESSAGE,
        ];
    }

    /**
     * @return array
     */
    public static function fraudRecommendationCaptchaArray(): array
    {
        return [
            'severity' => self::BLOCK,
            'code'     => FraudRecommendation::CAPTCHA,
            'message'  => self::CAPTCHA_REQUIRED,
        ];
    }

    /**
     * @return array
     */
    public static function fraudRecommendationBlacklistArray(): array
    {
        return [
            'severity' => self::BLOCK,
            'code'     => FraudRecommendation::BLACKLIST,
            'message'  => self::BLACKLIST_REQUIRED,
        ];
    }

    /**
     * @deprecated used on session version10
     * @return array
     */
    public static function fraudRecommendationForceThreeDArray(): array
    {
        return [
            'severity' => self::BLOCK,
            'code'     => FraudRecommendation::FORCE_THREE_D,
            'message'  => self::FORCE_THREE_D,
        ];
    }
}
