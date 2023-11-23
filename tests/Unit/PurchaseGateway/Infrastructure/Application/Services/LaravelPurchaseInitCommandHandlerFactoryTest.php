<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Application\Services;

use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\ExistingMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\LaravelPurchaseInitCommandHandlerFactory;
use Tests\UnitTestCase;

class LaravelPurchaseInitCommandHandlerFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_existing_member_command_handler_when_member_id_is_provided()
    {
        $memberId = $this->faker->uuid;

        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(ExistingMemberInitCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(ExistingMemberInitCommandHandler::class)
            ->willReturn($handler);


        $laravelPurchaseInitCommandHandlerFactory = new LaravelPurchaseInitCommandHandlerFactory($app);
        $laravelPurchaseInitCommandHandlerFactory->getHandler($memberId);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_new_member_command_handler_when_member_id_is_not_provided()
    {
        $memberId = null;

        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(NewMemberInitCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(NewMemberInitCommandHandler::class)
            ->willReturn($handler);


        $laravelPurchaseInitCommandHandlerFactory = new LaravelPurchaseInitCommandHandlerFactory($app);
        $laravelPurchaseInitCommandHandlerFactory->getHandler($memberId);
    }
}
