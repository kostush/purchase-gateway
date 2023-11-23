<?php

declare(strict_types=1);

namespace App\Providers;

use Doctrine\ORM\EntityManager;
use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEventPublisher;
use ProBillerNG\PurchaseGateway\Application\Services\PersistIntegrationEventSubscriber;
use ProBillerNG\PurchaseGateway\Domain\EventTracker;
use ProBillerNG\PurchaseGateway\Domain\Repository\EventTrackerRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\DoctrineEventTracker;

class CreatePurchaseIntegrationEventServiceProvider extends ServiceProvider
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

        // Creating my persistence subscriber to save all domain events sent
        $persistDomainEventSubscriber = $this->app->make(PersistIntegrationEventSubscriber::class);

        // Adding the subscriber
        IntegrationEventPublisher::instance()->subscribe($persistDomainEventSubscriber);
    }

    /**
     * @return void
     */
    public function register(): void
    {
        /** @var EntityManager $em */
        $em = $this->app['em'];

        $this->app->bind(
            EventTrackerRepository::class,
            function () use ($em) {
                return new DoctrineEventTracker(
                    $em,
                    $em->getClassMetadata(EventTracker::class)
                );
            }
        );
    }
}
