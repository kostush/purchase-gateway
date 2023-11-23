<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\NuData\Domain\Repository\NuDataSettingsRepository;
use ProBillerNG\NuData\Domain\Services\RetrieveNuDataScoreService as RetrieveNuDataScoreServiceInterface;
use ProBillerNG\NuData\Infrastructure\Domain\Services\RetrieveNuDataScoreService;
use ProBillerNG\NuData\Infrastructure\Domain\Repository\ValueStoreNuDataSettingsRepository;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService as NuDataInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\NuDataService;

class NuDataServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            NuDataInterface::class,
            NuDataService::class
        );

        $this->app->bind(
            NuDataSettingsRepository::class,
            function () {
                return new ValueStoreNuDataSettingsRepository(env('APP_ENV', 'local'));
            }
        );

        $this->app->bind(
            RetrieveNuDataScoreServiceInterface::class,
            RetrieveNuDataScoreService::class
        );
    }
}
