<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\CircuitBreaker\BadRequestException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateServiceAdapter;
use Tests\UnitTestCase;

class ValidatePaymentTemplateCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory(
            [
                ValidatePaymentTemplateCommand::class => [
                    'circuitBreaker' => [
                        'forceOpen' => true
                    ]
                ]
            ]
        )->getCommand(
            ValidatePaymentTemplateCommand::class,
            $adapterMock,
            $this->faker->uuid,
            '1234',
            $this->faker->uuid
        );

        try {
            $command->execute();
        } catch (\RuntimeException $exception) {
            $this->assertEquals('Short-circuited and failed retrieving fallback', $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_a_retrieve_payment_template_exception_when_unhandled_exception_encountered_with_previous_exception_set(): void
    {
        $exceptionThrown = new \Exception('Test Exception');
        $adapterMock     = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException($exceptionThrown);

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            ValidatePaymentTemplateCommand::class,
            $adapterMock,
            $this->faker->uuid,
            '1234',
            $this->faker->uuid
        );

        try {
            $command->execute();
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf(RetrievePaymentTemplateException::class, $exception->getFallbackException());
            $this->assertEquals($exceptionThrown->getMessage(), $exception->getFallbackException()->getPrevious()->getMessage());
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_a_bad_request_exception_with_validation_exception_encased_when_validation_error_encountered(): void
    {
        $exceptionThrown = new InvalidPaymentTemplateLastFour();
        $adapterMock     = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException($exceptionThrown);

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            ValidatePaymentTemplateCommand::class,
            $adapterMock,
            $this->faker->uuid,
            '1234',
            $this->faker->uuid
        );

        try {
            $command->execute();
        } catch (\Exception $exception) {
            $this->assertInstanceOf(BadRequestException::class, $exception);
            $this->assertInstanceOf(InvalidPaymentTemplateLastFour::class, $exception->getPrevious());
        }
    }
}
