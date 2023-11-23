<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\RetrieveFraudAdviceCsCommand;
use Tests\UnitTestCase;

class RetrieveFraudAdviceCsCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_modify_the_payment_template_collection_when_an_exception_is_encountered(): void
    {
        $adapterMock = $this->createMock(FraudAdviceCsAdapter::class);
        $adapterMock->method('retrieveAdvice')->willThrowException(new \Exception());
        $collection = $this->createMock(PaymentTemplateCollection::class);
        $collection->expects($this->never())->method('toArray');

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveFraudAdviceCsCommand::class,
            $adapterMock,
            $collection,
            $this->faker->uuid
        );

        $command->execute();
    }
}
