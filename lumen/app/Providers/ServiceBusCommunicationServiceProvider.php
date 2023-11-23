<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JMS\Serializer\SerializerBuilder;
use Laravel\Lumen\Application;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\PurchaseGatewayCommandFactory;
use ProBillerNG\PurchaseGateway\Application\PurchaseGatewayErrorClassifier;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\ConsumerCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\ServiceBus\DefaultMessageHandler;
use ProBillerNG\ServiceBus\EndPointConfiguration;
use ProBillerNG\ServiceBus\LaravelServiceLocator;
use ProBillerNG\ServiceBus\RabbitMq\ConsistentHashingRoutingTopology;
use ProBillerNG\ServiceBus\RabbitMq\DelayQueueStrategy;
use ProBillerNG\ServiceBus\RabbitMq\Transport;
use ProBillerNG\ServiceBus\RabbitMq\TransportConfig;
use ProBillerNG\ServiceBus\ServiceBus;

class ServiceBusCommunicationServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            CreateLegacyImportEventCommandHandler::class,
            function () {
                $serviceLocator = new LaravelServiceLocator();

                /** @var TransactionService $transactionService */
                $transactionService = $serviceLocator->make(TransactionService::class);
                /** @var BundleRepositoryReadOnly $bundleRepository */
                $bundleRepository = $serviceLocator->make(BundleRepositoryReadOnly::class);
                /** @var PaymentTemplateTranslatingService $paymentTemplateService */
                $paymentTemplateService = $serviceLocator->make(PaymentTemplateTranslatingService::class);
                /** @var ServiceBusFactory $serviceBusFactory */
                $serviceBusFactory = $serviceLocator->make(ServiceBusFactory::class);
                /** @var ConfigService $configServiceClient */
                $configServiceClient = $serviceLocator->make(ConfigService::class);
                return new ConsumerCommandHandler(
                    new CreateLegacyImportEventCommandHandler(
                        $transactionService,
                        $bundleRepository,
                        $paymentTemplateService,
                        $serviceBusFactory,
                        $configServiceClient
                    )
                );
            }
        );

        $this->app->bind(
            CreateMemberProfileEnrichedEventCommandHandler::class,
            function () {
                $serviceLocator = new LaravelServiceLocator();

                /** @var TransactionService $transactionService */
                $transactionService = $serviceLocator->make(TransactionService::class);
                /** @var BundleRepositoryReadOnly $bundleRepository */
                $bundleRepository = $serviceLocator->make(BundleRepositoryReadOnly::class);
                /** @var ServiceBusFactory $serviceBusFactory */
                $serviceBusFactory = $serviceLocator->make(ServiceBusFactory::class);
                /** @var ConfigService $configServiceClient */
                $configServiceClient = $serviceLocator->make(ConfigService::class);
                return new ConsumerCommandHandler(
                    new CreateMemberProfileEnrichedEventCommandHandler(
                        $transactionService,
                        $bundleRepository,
                        $serviceBusFactory,
                        $configServiceClient
                    )
                );
            }
        );

        $this->app->bind(
            ServiceBus::class,
            function (Application $app, array $params) {
                $serviceLocator = new LaravelServiceLocator();
                /** @var TransportConfig $config */
                $config = $serviceLocator->make(TransportConfig::class);

                $transport = new Transport(
                    $config,
                    new ConsistentHashingRoutingTopology(
                        (int) config('queue.connection.options.queue_number_' . $params['consumerName']),
                        $params['queueToConsume']
                    ),
                    SerializerBuilder::create()->build(),
                    Log::getInstance(),
                    new DelayQueueStrategy(1, 16)
                );

                $endpoint = new EndPointConfiguration(
                    config('app.name') . '.' . $params['consumerName'],
                    $transport
                );

                $this->subscribeToEventsByConsumer($params['consumerName'], $endpoint);

                $endpoint->addExceptionClassifier(new PurchaseGatewayErrorClassifier());

                /**
                 * @var $purchaseGatewayCommandFactory PurchaseGatewayCommandFactory
                 */
                $purchaseGatewayCommandFactory = $serviceLocator->make(PurchaseGatewayCommandFactory::class);
                return new ServiceBus(
                    $endpoint,
                    new DefaultMessageHandler(
                        $serviceLocator,
                        $purchaseGatewayCommandFactory,
                        $endpoint
                    )
                );
            }
        );
    }

    /**
     * @param string                $consumerName          Consumer name
     * @param EndPointConfiguration $endPointConfiguration End point configuration
     *
     * @return void
     */
    protected function subscribeToEventsByConsumer(
        string $consumerName,
        EndPointConfiguration $endPointConfiguration
    ): void {
        switch ($consumerName) {
            case CreateLegacyImportEventCommandHandler::WORKER_NAME:
                $endPointConfiguration->subscribeToEvent(
                    PurchaseProcessed::class,
                    CreateLegacyImportEventCommandHandler::class
                );
                break;
            case CreateMemberProfileEnrichedEventCommandHandler::WORKER_NAME:
                $endPointConfiguration->subscribeToEvent(
                    PurchaseProcessed::class,
                    CreateMemberProfileEnrichedEventCommandHandler::class
                );
                break;
        }
    }
}
