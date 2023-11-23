<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyRebillPostbackCommandHandler;

class LaravelThirdPartyPostbackCommandHandlerFactory implements ThirdPartyPostbackCommandHandlerFactory
{
    /**
     * @var Application
     */
    private $app;

    /**
     * LaravelPurchaseCommandHandlerFactory constructor.
     * @param Application $app Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $postbackType Postback type.
     * @return BasePaymentProcessCommandHandler
     */
    public function getHandler(string $postbackType): BasePaymentProcessCommandHandler
    {
        switch ($postbackType) {
            case ThirdPartyPostbackCommandHandlerFactory::REBILL:
                return $this->app->make(ThirdPartyRebillPostbackCommandHandler::class);
            case ThirdPartyPostbackCommandHandlerFactory::CHARGE:
            default:
                return $this->app->make(ThirdPartyPostbackCommandHandler::class);
        }
    }
}
