<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent\RetrieveIntegrationEventQueryHandler;
use ProBillerNG\PurchaseGateway\Application\DTO\IntegrationEventDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\HttpQueryDTOAssembler;

class RetrieveIntegrationEventServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app
            ->when(RetrieveIntegrationEventQueryHandler::class)
            ->needs(IntegrationEventDTOAssembler::class)
            ->give(HttpQueryDTOAssembler::class);
    }
}
