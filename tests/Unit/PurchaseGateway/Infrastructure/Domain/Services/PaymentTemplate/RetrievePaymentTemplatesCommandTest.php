<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesCommand;
use Tests\UnitTestCase;

class RetrievePaymentTemplatesCommandTest extends UnitTestCase
{
    /**
     * @test
     * @throws \Odesk\Phystrix\Exception\ApcNotLoadedException
     * @return void
     */
    public function it_should_return_an_object_when_the_circuit_is_opened(): void
    {
        $adapterMock = $this->createMock(RetrievePaymentTemplatesServiceAdapter::class);
        $adapterMock->method('retrieveAllPaymentTemplates')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrievePaymentTemplatesCommand::class,
            $adapterMock,
            $this->faker->uuid,
            $this->createBillerCollection(),
            $this->faker->word,
            $this->faker->uuid
        );

        $result = $command->execute();

        $this->assertInstanceOf(PaymentTemplateCollection::class, $result);
    }

    /**
     * @test
     * @throws \Odesk\Phystrix\Exception\ApcNotLoadedException
     * @return void
     */
    public function it_should_return_an_empty_collection_when_the_circuit_is_opened(): void
    {
        $adapterMock = $this->createMock(RetrievePaymentTemplatesServiceAdapter::class);
        $adapterMock->method('retrieveAllPaymentTemplates')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrievePaymentTemplatesCommand::class,
            $adapterMock,
            $this->faker->uuid,
            $this->createBillerCollection(),
            $this->faker->word,
            $this->faker->uuid
        );

        $result = $command->execute();

        $this->assertEquals(new PaymentTemplateCollection(), $result);
    }
}
