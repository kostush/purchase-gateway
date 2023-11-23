<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JMS\Serializer\SerializerBuilder;
use ProBillerNG\Base\Domain\DomainEventPublisher;
use ProBillerNG\PurchaseGateway\Domain\PublishDomainEventSubscriber;
use ProBillerNG\ServiceBus\RabbitMq\TransportConfig;
use ProBillerNG\ServiceBus\LaravelServiceLocator;
use ProBillerNG\ServiceBus\RabbitMq\DefaultRoutingTopology;
use ProBillerNG\ServiceBus\RabbitMq\Transport;

class PublishingServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws \Exception
     */
    public function boot(): void
    {
        // Creating the subscriber that publishes messages
        $publishDomainEventSubscriber = $this->app->make(PublishDomainEventSubscriber::class);

        // Adding the subscriber
        DomainEventPublisher::instance()->subscribe($publishDomainEventSubscriber);
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            TransportConfig::class,
            function () {
                return new TransportConfig(
                    config('queue.connection.host'),
                    config('queue.connection.user'),
                    config('queue.connection.pass'),
                    config('queue.connection.options'),
                    config('queue.connection.connectionTimeout')
                );
            }
        );

        $this->app->bind(
            Transport::class,
            function () {
                $serviceLocator = new LaravelServiceLocator();

                /** @var  TransportConfig $config */
                $config = $serviceLocator->make(TransportConfig::class);

                return new Transport(
                    $config,
                    new DefaultRoutingTopology(),
                    SerializerBuilder::create()->build()
                );
            }
        );
    }
}
