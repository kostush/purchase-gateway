<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;

class FraudRecommendationHelper
{
    const FILTER_LIST = [ChequePaymentInfo::PAYMENT_TYPE => [FraudRecommendation::FORCE_THREE_D]];

    /**
     * @param FraudRecommendationCollection $fraudRecommendationCollection
     * @param string                        $paymentType
     *
     * @return FraudRecommendationCollection
     */
    public static function filterFraudRecommendationByPaymentType(
        FraudRecommendationCollection $fraudRecommendationCollection,
        string $paymentType
    ): FraudRecommendationCollection {
        if (!array_key_exists($paymentType, self::FILTER_LIST)) {
            Log::info("FraudRecommendationFilter Non filter configured for paymentType", [$paymentType]);

            return $fraudRecommendationCollection;
        }

        Log::info(
            "FraudRecommendationFilter Filtering recommendations for paymentType",
            [
                'paymentType' => $paymentType,
                'filterList'  => self::FILTER_LIST[$paymentType]
            ]
        );

        $filteredCollection = $fraudRecommendationCollection->filter(
            function (FraudRecommendation $fraudRecommendation) use ($paymentType) {
                if (in_array($fraudRecommendation->code(), self::FILTER_LIST[$paymentType])) {
                    return false;
                }

                return true;
            }
        );
        if ($filteredCollection->isEmpty()) {
            $filteredCollection->add(FraudRecommendation::createDefaultAdvice());
            Log::info(
                "FraudRecommendationFilter Filtered recommendation list became empty, adding default recommendation",
                [
                    'finalList' => $filteredCollection
                ]
            );
        }

        $filteredCollection = self::defineDefaultFraudRecommendationByPaymentType($paymentType, $filteredCollection);

        return $filteredCollection;
    }

    /**
     * @param string $paymentType
     * @param FraudRecommendationCollection $fraudRecommendationCollection
     * 
     * @return FraudRecommendationCollection
     */
    public static function defineDefaultFraudRecommendationByPaymentType(string $paymentType, FraudRecommendationCollection $fraudRecommendationCollection): FraudRecommendationCollection
    {
        return $fraudRecommendationCollection->map(
            function($fraudRecommendation) use ($paymentType) {
                if ($paymentType === ChequePaymentInfo::PAYMENT_TYPE && $fraudRecommendation->isDefault())
                {
                    Log::info("FraudRecommendationDefault Replacing default fraud recommendation to catpcha for checks payment");
                    return FraudRecommendation::createCaptchaAdvice();
                }
                return $fraudRecommendation;
            }
        );
    }

    /**
     * @param PurchaseProcess $purchaseProcess
     * 
     * @return void
     */
    public static function setDefaultFraudRecommendationOnInit(PurchaseProcess $purchaseProcess): void
    {
        if ($purchaseProcess->paymentInfo()->paymentType() === ChequePaymentInfo::PAYMENT_TYPE)
        {
            Log::info("FraudRecommendationDefault Setting default fraud recommendation to catpcha for checks payment");

            $fraudRecommendationCollection = new FraudRecommendationCollection([FraudRecommendation::createCaptchaAdvice()]);
            
            $purchaseProcess->setFraudRecommendationCollection($fraudRecommendationCollection);
            $purchaseProcess->setFraudAdvice(
                FraudIntegrationMapper::mapFraudRecommendationToFraudAdviceOnInit(
                    $fraudRecommendationCollection,
                    $purchaseProcess->fraudAdvice()
                )
            );
            return;
        }
        $purchaseProcess->setFraudAdvice(FraudAdvice::create());
        $purchaseProcess->setFraudRecommendation(FraudRecommendation::createDefaultAdvice());
    }

}