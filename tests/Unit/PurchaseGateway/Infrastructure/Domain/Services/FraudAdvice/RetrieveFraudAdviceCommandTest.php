<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\RetrieveFraudAdviceCommand;
use Tests\UnitTestCase;

class RetrieveFraudAdviceCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_fraud_advice_when_an_exception_is_encountered(): void
    {
        $adapterMock = $this->createMock(FraudAdviceAdapter::class);
        $adapterMock->method('retrieveAdvice')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveFraudAdviceCommand::class,
            $adapterMock,
            SiteId::create(),
            [],
            FraudAdvice::FOR_INIT,
            SessionId::create()
        );

        $fraudAdvice = $command->execute();

        $this->assertInstanceOf(FraudAdvice::class, $fraudAdvice);
    }
}
