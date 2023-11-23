<?php
declare(strict_types=1);

namespace App\Providers;

use Doctrine\ORM\EntityManager;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use Laravel\Lumen\Application;
use LVR\CountryCode\Three;
use LVR\CountryCode\Two;
use Odesk\Phystrix\ApcStateStorage;
use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\CommandMetricsFactory;
use Odesk\Phystrix\RequestCache;
use Odesk\Phystrix\RequestLog;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\Base\Domain\DomainEventPublisher;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\FailedEventPublish;
use ProBillerNG\PurchaseGateway\Domain\PersistDomainEventSubscriber;
use ProBillerNG\PurchaseGateway\Domain\Repository\FailedEventPublishRepository as FailedEventPublishRepositoryInterface;
use ProBillerNG\PurchaseGateway\Domain\Repository\PostbackJobsRepositoryInterface;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceStatusVerifier;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DoctrineSession;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\LaravelPurchaseInitCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\LaravelThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\CircuitBreakerVerifier;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\ConvertingEventStore;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\DoctrineEventStore;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\FailedEventPublishRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PostbackJobsRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback\LumenJob as PostbackLumenJob;
use Zend\Config\Config;
use Zend\Di\ServiceLocator;

class ApplicationServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws \Exception
     */
    public function boot(): void
    {
        if (!file_exists(storage_path('framework/sessions'))) {
            mkdir(storage_path('framework/sessions'), 0777);
        }

        // force any request to be https
        if (!$this->app->environment('local')) {
            URL::forceScheme('https');
        }

        // Add custom validation rules
        Validator::extend('country_two', function ($attribute, $value, $parameters, $validator) {
                return (new Two)->passes($attribute, $value);
            }
        );

        Validator::extend('country_three', function ($attribute, $value, $parameters, $validator) {
                return (new Three)->passes($attribute, $value);
            }
        );

        Validator::extend('integer_only', function($attribute, $value, $parameters) {
            return is_int($value);
        });

        Validator::extend('alpha_spaces', function ($attribute, $value) {
                // This will only accept alpha and spaces.
                // If you want to accept hyphens use: /^[\pL\s-]+$/u.
                return preg_match('/^[\pL\s]+$/u', $value);
            }
        );

        // Creating my persistence subscriber to save all domain events sent
        $persistDomainEventSubscriber = $this->app->make(PersistDomainEventSubscriber::class);

        // Adding the subscriber
        DomainEventPublisher::instance()->subscribe($persistDomainEventSubscriber);
    }

    /**
     * @return void
     */
    public function register(): void
    {
        /** @var EntityManager $em */
        $em = $this->app['em'];

        // Transactional Session
        $this->app->bind(
            TransactionalSession::class,
            function () use ($em) {
                return new DoctrineSession($em);
            }
        );

        // Failed event publish repository
        $this->app->bind(
            FailedEventPublishRepositoryInterface::class,
            function () use ($em) {
                return new FailedEventPublishRepository($em, $em->getClassMetadata(FailedEventPublish::class));
            }
        );

        // Event Store
        $this->app->bind(EventStore::class, ConvertingEventStore::class);

        $this->app->when(ConvertingEventStore::class)
            ->needs(EventStore::class)
            ->give(
                function () use ($em) {
                    return new DoctrineEventStore(
                        $em,
                        $em->getClassMetadata(StoredEvent::class),
                        SerializerBuilder::create()
                            ->addDefaultHandlers()
                            ->configureHandlers(
                                function (HandlerRegistry $registry) {
                                    $classes = [
                                        \DateTimeImmutable::class
                                    ];
                                    // Custom handling for dateTimeImmutable to allow serializer to add microseconds
                                    // This is a JMS limitation
                                    foreach ($classes as $class) {
                                        $registry->registerHandler(
                                            GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                                            $class,
                                            'json',
                                            function ($visitor, $obj, array $type) {
                                                return (string) $obj->format('Y-m-d H:i:s.u');
                                            }
                                        );
                                    }
                                }
                            )->build()
                    );
                }
            );

        $this->app->bind(
            PostbackService::class,
            PostbackLumenJob::class
        );

        $this->app->bind(PostbackJobsRepositoryInterface::class, PostbackJobsRepository::class);

        // Circuit breaker configuration read
        $this->app->bind(ServiceStatusVerifier::class, CircuitBreakerVerifier::class);

        // Circuit breaker configuration
        $this->app->singleton(
            CommandFactory::class,
            function (Application $app) {
                $config = new Config($app['config']->get("phystrix"));

                $stateStorage          = new ApcStateStorage();
                $circuitBreakerFactory = new CircuitBreakerFactory($stateStorage);
                $commandMetricsFactory = new CommandMetricsFactory($stateStorage);

                $commandFactory = new CommandFactory(
                    $config,
                    new ServiceLocator(),
                    $circuitBreakerFactory,
                    $commandMetricsFactory,
                    new RequestCache(),
                    new RequestLog()
                );

                return $commandFactory;
            }
        );

        $this->app->bind(
            PurchaseInitCommandHandlerFactory::class,
            LaravelPurchaseInitCommandHandlerFactory::class
        );

        $this->app->bind(
            ThirdPartyPostbackCommandHandlerFactory::class,
            LaravelThirdPartyPostbackCommandHandlerFactory::class
        );
    }
}
