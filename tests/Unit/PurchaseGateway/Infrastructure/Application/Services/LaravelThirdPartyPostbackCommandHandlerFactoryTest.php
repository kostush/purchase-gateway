<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Application\Services;

use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyRebillPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\LaravelThirdPartyPostbackCommandHandlerFactory;
use Tests\UnitTestCase;

class LaravelThirdPartyPostbackCommandHandlerFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_third_party_postback_command_handler_when_postback_type_is_charge()
    {
        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(ThirdPartyPostbackCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(ThirdPartyPostbackCommandHandler::class)
            ->willReturn($handler);


        $laravelPurchaseInitCommandHandlerFactory = new LaravelThirdPartyPostbackCommandHandlerFactory($app);
        $laravelPurchaseInitCommandHandlerFactory->getHandler(ThirdPartyPostbackCommandHandlerFactory::CHARGE);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_third_party_rebill_postback_command_handler_when_postback_type_is_rebill()
    {
        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(ThirdPartyRebillPostbackCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(ThirdPartyRebillPostbackCommandHandler::class)
            ->willReturn($handler);


        $laravelPurchaseInitCommandHandlerFactory = new LaravelThirdPartyPostbackCommandHandlerFactory($app);
        $laravelPurchaseInitCommandHandlerFactory->getHandler(ThirdPartyPostbackCommandHandlerFactory::REBILL);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_third_party_postback_command_handler_when_postback_type_unknown()
    {
        $app     = $this->createMock(Application::class);
        $handler = $this->createMock(ThirdPartyPostbackCommandHandler::class);
        $app->expects($this->once())
            ->method('make')
            ->with(ThirdPartyPostbackCommandHandler::class)
            ->willReturn($handler);


        $laravelPurchaseInitCommandHandlerFactory = new LaravelThirdPartyPostbackCommandHandlerFactory($app);
        $laravelPurchaseInitCommandHandlerFactory->getHandler('unknown');
    }
}
