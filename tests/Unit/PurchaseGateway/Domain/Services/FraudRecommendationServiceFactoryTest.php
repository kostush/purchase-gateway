<?php

namespace PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewCardOnProcessTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewChequeOnProcessTranslatingService;
use Tests\UnitTestCase;

class FraudRecommendationServiceFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_cheque_translate_service(): void
    {
        $factory = new FraudRecommendationServiceFactory();

        $translateService = $factory->buildFraudRecommendationForPaymentOnProcess('checks');
        $this->assertInstanceOf(
            RetrieveFraudRecommendationForNewChequeOnProcessTranslatingService::class,
            $translateService
        );
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_cc_translate_service(): void
    {
        $factory = new FraudRecommendationServiceFactory();

        $translateService = $factory->buildFraudRecommendationForPaymentOnProcess('cc');
        $this->assertInstanceOf(
            RetrieveFraudRecommendationForNewCardOnProcessTranslatingService::class,
            $translateService
        );
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_cc_translate_service_when_a_different_payment_type_is_passed(): void
    {
        $factory = new FraudRecommendationServiceFactory();

        $translateService = $factory->buildFraudRecommendationForPaymentOnProcess('giftcards');
        $this->assertInstanceOf(
            RetrieveFraudRecommendationForNewCardOnProcessTranslatingService::class,
            $translateService
        );
    }
}