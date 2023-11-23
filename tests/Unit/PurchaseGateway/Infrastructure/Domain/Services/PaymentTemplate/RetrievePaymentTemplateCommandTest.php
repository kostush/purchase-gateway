<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplateCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplateServiceAdapter;
use Tests\UnitTestCase;

class RetrievePaymentTemplateCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(RetrievePaymentTemplateServiceAdapter::class);
        $adapterMock->method('retrievePaymentTemplate')->willThrowException(new RuntimeException('', RetrievePaymentTemplateCommand::class));

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrievePaymentTemplateCommand::class,
            $adapterMock,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $command->execute();
    }
}
