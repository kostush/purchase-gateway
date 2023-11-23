<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\Services\BaseCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\PurchaseProcessCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ExistingPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ThirdPartyPaymentProcessCommandHandler;

class LaravelPurchaseProcessCommandHandlerFactory implements PurchaseProcessCommandHandlerFactory
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
     * @param array|null $payment Payment
     * @return BaseCommandHandler
     */
    public function getHandler(?array $payment): BaseCommandHandler
    {
        if (!empty($payment['cardInformation']) || !empty($payment['ccNumber'])) {
            return $this->app->make(NewPaymentProcessCommandHandler::class);
        }

        /** As of now, We have only rocketgate biller for cheque purchase */
        if (!empty($payment['checkInformation'])) {
            return $this->app->make(NewPaymentProcessCommandHandler::class);
        }

        if (!empty($payment['paymentTemplateInformation'])) {
            return $this->app->make(ExistingPaymentProcessCommandHandler::class);
        }

        if (isset($payment['method']) && !empty($payment['method'])) {
            return $this->app->make(ThirdPartyPaymentProcessCommandHandler::class);
        }

        return $this->app->make(NewPaymentProcessCommandHandler::class);
    }
}
