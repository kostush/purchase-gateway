<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Laravel\Lumen\Application;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Crypt;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;
use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\HttpQueryHealthDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\PurchaseGatewayHealthDTOAssembler;
use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class PurchaseGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            PurchaseGatewayHealthDTOAssembler::class,
            HttpQueryHealthDTOAssembler::class
        );

        $this->app->bind(
            Init\PurchaseInitDTOAssembler::class,
            Init\HttpCommandDTOAssembler::class
        );

        $this->app->bind(
            Process\ProcessPurchaseDTOAssembler::class,

            function () {
                return new Process\HttpCommandDTOAssembler(
                    app(TokenGenerator::class),
                    app(ConfigService::class)->getSite(app(Request::class)->input('siteId')),
                    app(CryptService::class)
                );
            }

        );

        $this->app->singleton(
            BILoggerService::class,
            function () {
                $biLogger = new BILoggerService();
                $biLogger->initializeConfig(storage_path() . '/logs/' . env('BI_LOG_FILE'));

                return $biLogger;
            }
        );

        $this->app->singleton(
            Crypt::class,
            function () {
                return new PrivateKeyCypher(
                    new PrivateKeyConfig(
                        env('APP_CRYPT_KEY')
                    )
                );
            }
        );
    }
}
