<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\PurchaseProcessCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenDecoder;
use ProBillerNG\PurchaseGateway\Domain\Repository\PurchaseProcessRepositoryInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DatabasePurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenDecoder;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\LaravelPurchaseProcessCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\ConvertingPurchaseProcessRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;

class PurchaseProcessServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(PurchaseProcessHandler::class, DatabasePurchaseProcessHandler::class);

        $this->app->bind(PurchaseProcessRepositoryInterface::class, ConvertingPurchaseProcessRepository::class);

        $this->app->when(ConvertingPurchaseProcessRepository::class)
            ->needs(PurchaseProcessRepositoryInterface::class)
            ->give(
                function (Application $application) {
                    return new PurchaseProcessRepository($application['em']);
                }
            );

        $this->app->bind(TokenDecoder::class, JsonWebTokenDecoder::class);

        $this->app->bind(
            PurchaseProcessCommandHandlerFactory::class,
            LaravelPurchaseProcessCommandHandlerFactory::class
        );

        $this->app->bind(
            AuthenticateThreeDDTOAssembler::class,
            AuthenticateThreeDQueryDTOAssembler::class
        );

        $this->app->bind(
            ThirdPartyRedirectDTOAssembler::class,
            ThirdPartyRedirectQueryDTOAssembler::class
        );

        $this->app->bind(
            CompleteThreeDDTOAssembler::class,
            CompleteThreeDCommandDTOAssembler::class
        );

        $this->app->bind(
            LookupThreeDDTOAssembler::class,
            LookupThreeDCommandDTOAssembler::class
        );
    }
}
