<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Application\Services;

use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ExistingPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\LaravelPurchaseProcessCommandHandlerFactory;
use Tests\UnitTestCase;

class LaravelPurchaseProcessCommandHandlerFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_existing_payment_command_handler_when_template_id_is_present_in_payload()
    {
        $payment['paymentTemplateInformation']['paymentTemplateId'] = $this->faker->uuid;

        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(ExistingPaymentProcessCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(ExistingPaymentProcessCommandHandler::class)
            ->willReturn($handler);

        $laravelPurchaseProcessCommandHandlerFactory = new LaravelPurchaseProcessCommandHandlerFactory($app);
        $laravelPurchaseProcessCommandHandlerFactory->getHandler($payment);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_new_payment_command_handler_when_ccNumber_and_templateId_are_both_in_payload()
    {
        $payment['cardInformation']['ccNumber']                     = $this->faker->creditCardNumber;
        $payment['paymentTemplateInformation']['paymentTemplateId'] = $this->faker->uuid;

        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(NewPaymentProcessCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(NewPaymentProcessCommandHandler::class)
            ->willReturn($handler);

        $laravelPurchaseProcessCommandHandlerFactory = new LaravelPurchaseProcessCommandHandlerFactory($app);
        $laravelPurchaseProcessCommandHandlerFactory->getHandler($payment);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_new_payment_command_handler_when_template_id_is_not_present_in_payload()
    {
        $payment['paymentTemplateInformation'] = [];

        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(NewPaymentProcessCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(NewPaymentProcessCommandHandler::class)
            ->willReturn($handler);

        $laravelPurchaseProcessCommandHandlerFactory = new LaravelPurchaseProcessCommandHandlerFactory($app);
        $laravelPurchaseProcessCommandHandlerFactory->getHandler($payment);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_new_payment_command_handler_when_check_information_are_in_payload()
    {
        $payment['method']                            = "checks";
        $payment['checkInformation']['routingNumber'] = $this->faker->numerify('########');
        $payment['checkInformation']['accountNo']     = $this->faker->numerify('######');

        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(NewPaymentProcessCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(NewPaymentProcessCommandHandler::class)
            ->willReturn($handler);

        $laravelPurchaseProcessCommandHandlerFactory = new LaravelPurchaseProcessCommandHandlerFactory($app);
        $laravelPurchaseProcessCommandHandlerFactory->getHandler($payment);
    }
}
