<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProbillerNG\CascadeServiceClient\Api\CascadeServiceApi;
use ProbillerNG\CascadeServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\RetrieveCascadeAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\RetrieveInMemoryCascadeAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CircuitBreakerRetrieveCascadeAdapter;

/**
 * Class CascadeServiceProvider
 * @package App\Providers
 */
class CascadeServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        // Register the Cascade client implementation
        $this->app->bind(CascadeTranslatingService::class, RetrieveCascadeTranslatingService::class);

        $adapter = config('app.feature.cascade_service_enabled') ? RetrieveCascadeAdapter::class : RetrieveInMemoryCascadeAdapter::class;
        $this->app->bind(
            CascadeAdapter::class,
            function () use ($adapter) {
                return new CircuitBreakerRetrieveCascadeAdapter(
                    $this->app->make(CommandFactory::class),
                    $this->app->make($adapter)
                );
            }
        );

        $this->app->bind(
            CascadeClient::class,
            function (Application $application) {
                return new CascadeClient(
                    new CascadeServiceApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.cascadeService.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.cascadeService.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($application['config']->get('clientapis.cascadeService.accessToken'))
                            ->setUsername($application['config']->get('clientapis.cascadeService.username'))
                            ->setPassword($application['config']->get('clientapis.cascadeService.password'))
                            ->setHost($application['config']->get('clientapis.cascadeService.host'))
                            ->setUserAgent($application['config']->get('clientapis.cascadeService.userAgent'))
                            ->setDebug($application['config']->get('clientapis.cascadeService.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.cascadeService.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.cascadeService.tempFolderPath')))
                    )
                );
            }
        );
    }
}
