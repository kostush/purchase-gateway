<?php
declare(strict_types=1);

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping\BillerMappingTranslatingService;

class BillerMappingServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            BillerMappingService::class,
            BillerMappingTranslatingService::class
        );
    }
}
