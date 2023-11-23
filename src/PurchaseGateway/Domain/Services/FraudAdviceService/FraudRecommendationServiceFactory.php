<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewPaymentOnProcess;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewCardOnProcessTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewChequeOnProcessTranslatingService;

class FraudRecommendationServiceFactory
{
    /**
     * @param $paymentType
     *
     * @return RetrieveFraudRecommendationForNewPaymentOnProcess
     * @throws \ProBillerNG\Logger\Exception
     */
    public function buildFraudRecommendationForPaymentOnProcess(
        $paymentType
    ): RetrieveFraudRecommendationForNewPaymentOnProcess {

        if ($paymentType == ChequePaymentInfo::PAYMENT_TYPE) {
            Log::info(
                'BuildFraudRecommendationProcess Building ACH fraud recommendation implementation.',
                ['paymentType' => $paymentType]
            );

            return app()->make(RetrieveFraudRecommendationForNewChequeOnProcessTranslatingService::class);
        }
        Log::info(
            'BuildFraudRecommendationProcess Building credit card fraud recommendation implementation.',
            ['paymentType' => $paymentType]
        );

        return app()->make(RetrieveFraudRecommendationForNewCardOnProcessTranslatingService::class);
    }
}