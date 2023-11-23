<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\CircuitBreakerValidatePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateServiceAdapter;
use Tests\UnitTestCase;

class CircuitBreakerValidatePaymentTemplateAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_invalid_payment_template_last_four_exception_when_last_four_invalid(): void
    {
        $this->expectException(InvalidPaymentTemplateLastFour::class);

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new InvalidPaymentTemplateLastFour());

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $cbAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $cbAdapter->validatePaymentTemplate(
            $this->faker->uuid,
            $this->faker->randomNumber(4, true),
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_a_retrieve_payment_template_exception_when_client_throws_unhandled_error(): void
    {
        $this->expectException(RetrievePaymentTemplateException::class);

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new RetrievePaymentTemplateException());

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $cbAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $cbAdapter->validatePaymentTemplate(
            $this->faker->uuid,
            $this->faker->randomNumber(4, true),
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_a_retrieve_payment_template_exception_when_circuit_breaker_open(): void
    {
        $this->expectException(RetrievePaymentTemplateException::class);

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new RetrievePaymentTemplateException());

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $cbAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(
                        [
                            ValidatePaymentTemplateCommand::class => [
                                'circuitBreaker' => [
                                    'forceOpen' => true
                                ]
                            ]
                        ]
                    ),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $cbAdapter->validatePaymentTemplate(
            $this->faker->uuid,
            $this->faker->randomNumber(4, true),
            $this->faker->uuid
        );
    }
}
