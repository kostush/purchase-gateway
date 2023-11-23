<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnHttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackCommandDTOAssembler;

class ThirdPartyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(ReturnDTOAssembler::class, ReturnHttpCommandDTOAssembler::class);
        $this->app->bind(ThirdPartyPostbackDTOAssembler::class, ThirdPartyPostbackCommandDTOAssembler::class);
    }
}
