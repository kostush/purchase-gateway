<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationCommand;
use Tests\UnitTestCase;

class RetrieveFraudRecommendationCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_fraud_advice_when_an_exception_is_encountered(): void
    {
        $adapterMock = $this->createMock(RetrieveFraudRecommendationAdapter::class);
        $adapterMock->method('retrieve')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveFraudRecommendationCommand::class,
            $adapterMock,
            (string) BusinessGroupId::create(),
            (string) SiteId::create(),
            'event',
            [],
            'sessionId',
            []
        );

        $fraudAdvice = $command->execute();

        $this->assertInstanceOf(FraudRecommendationCollection::class, $fraudAdvice);
    }
}
