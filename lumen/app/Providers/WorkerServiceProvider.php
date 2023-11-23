<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use ProBillerNG\Projection\Domain\HandlerBuilder;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventBackupCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventBackupCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\DomainEventSource;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases\PurchaseProcessedRetriever;
use ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases\TimerPendingPurchasesCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\VoidTransactions\VoidTransactionsCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemSource\BundleAddonRetriever;
use ProBillerNG\PurchaseGateway\Domain\Projector\ItemSource\BusinessGroupSiteRetriever;

class WorkerServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            HandlerBuilder::class,
            function (Application $app) {
                $handlerBuilder = new HandlerBuilder();
                // Handler for legacy import
                $handlerBuilder->registerHandler(
                    CreateLegacyImportEventBackupCommandHandler::WORKER_NAME,
                    $app->make(CreateLegacyImportEventBackupCommandHandler::class)
                );
                // Handler for member profile enriched event
                $handlerBuilder->registerHandler(
                    CreateMemberProfileEnrichedEventBackupCommandHandler::WORKER_NAME,
                    $app->make(CreateMemberProfileEnrichedEventBackupCommandHandler::class)
                );
                // Handler for sending emails
                $handlerBuilder->registerHandler(
                    SendEmailsCommandHandler::WORKER_NAME,
                    $app->make(SendEmailsCommandHandler::class)
                );

                // Handler for timer worker
                $handlerBuilder->registerHandler(
                    TimerPendingPurchasesCommandHandler::WORKER_NAME,
                    $app->make(TimerPendingPurchasesCommandHandler::class)
                );

                // Handler for voiding transactions
                $handlerBuilder->registerHandler(
                    VoidTransactionsCommandHandler::WORKER_NAME,
                    $app->make(VoidTransactionsCommandHandler::class)
                );

                return $handlerBuilder;
            }
        );

        $this->app->singleton(
            ItemSourceBuilder::class,
            function (Application $app) {
                $itemSourceBuilder = new ItemSourceBuilder();
                // Item source for legacy import
                $itemSourceBuilder->registerItemSource(
                    CreateLegacyImportEventBackupCommandHandler::WORKER_NAME,
                    $app->make(DomainEventSource::class)
                );
                // Item source for member profile enriched event
                $itemSourceBuilder->registerItemSource(
                    CreateMemberProfileEnrichedEventBackupCommandHandler::WORKER_NAME,
                    $app->make(DomainEventSource::class)
                );
                // Item source for sending emails
                $itemSourceBuilder->registerItemSource(
                    SendEmailsCommandHandler::WORKER_NAME,
                    $app->make(DomainEventSource::class)
                );

                // Item source for timer worker
                $itemSourceBuilder->registerItemSource(
                    TimerPendingPurchasesCommandHandler::WORKER_NAME,
                    $app->make(PurchaseProcessedRetriever::class)
                );

                $itemSourceBuilder->registerItemSource(
                    BundleAddonsProjector::WORKER_NAME,
                    $app->make(BundleAddonRetriever::class)
                );

                $itemSourceBuilder->registerItemSource(
                    BusinessGroupSitesProjector::WORKER_NAME,
                    $app->make(BusinessGroupSiteRetriever::class)
                );

                // Item source for voiding transactions
                $itemSourceBuilder->registerItemSource(
                    VoidTransactionsCommandHandler::WORKER_NAME,
                    $app->make(DomainEventSource::class)
                );

                return $itemSourceBuilder;
            }
        );
    }
}
