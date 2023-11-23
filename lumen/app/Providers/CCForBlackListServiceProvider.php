<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;

class CCForBlackListServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            CCForBlackListService::class,
            CCForBlackListTranslatingService::class
        );
    }
}
