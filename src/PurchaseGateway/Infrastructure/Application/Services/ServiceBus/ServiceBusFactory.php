<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus;

use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayCommandFactory;
use ProBillerNG\ServiceBus\DefaultMessageHandler;
use ProBillerNG\ServiceBus\EndPointConfiguration;
use ProBillerNG\ServiceBus\LaravelServiceLocator;
use ProBillerNG\ServiceBus\RabbitMq\Transport;
use ProBillerNG\ServiceBus\ServiceBus;

class ServiceBusFactory
{
    /**
     * @return ServiceBus
     */
    public function make(): ServiceBus
    {
        $serviceLocator = new LaravelServiceLocator();

        /** @var  Transport $transport */
        $transport = $serviceLocator->make(Transport::class);

        $endPointConfiguration = new EndPointConfiguration(config('app.name'), $transport);

        return new ServiceBus(
            $endPointConfiguration,
            new DefaultMessageHandler(
                $serviceLocator,
                new PurchaseGatewayCommandFactory(),
                $endPointConfiguration
            )
        );
    }
}
