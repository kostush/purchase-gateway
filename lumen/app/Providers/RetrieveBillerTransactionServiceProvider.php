<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\BillerTransactionDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\HttpQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveBillerTransactionQueryHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Model\DoctrineItemRepository;

class RetrieveBillerTransactionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app
            ->when(RetrieveBillerTransactionQueryHandler::class)
            ->needs(BillerTransactionDTOAssembler::class)
            ->give(HttpQueryDTOAssembler::class);

        // Repository
        $em = $this->app['registry']->getManager('mysql-readonly');
        $this->app->bind(
            ItemRepositoryReadOnly::class,
            function () use ($em) {
                return new DoctrineItemRepository(
                    $em,
                    $em->getClassMetadata(ProcessedBundleItem::class)
                );
            }
        );
    }
}
