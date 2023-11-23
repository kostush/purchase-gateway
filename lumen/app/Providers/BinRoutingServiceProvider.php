<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use ProbillerNG\BinRoutingServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\BinRoutingClient;
use ProbillerNG\BinRoutingServiceClient\Api\BinRoutingApi;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\BinRoutingServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\NetbillingBinRoutingAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\NetbillingBinRoutingTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\RocketgateBinRoutingAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\RocketgateBinRoutingTranslatingService;

class BinRoutingServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app
            ->when(RocketgateBinRoutingTranslatingService::class)
            ->needs(BinRoutingServiceAdapter::class)
            ->give(RocketgateBinRoutingAdapter::class);

        $this->app
            ->when(NetbillingBinRoutingTranslatingService::class)
            ->needs(BinRoutingServiceAdapter::class)
            ->give(NetbillingBinRoutingAdapter::class);

        $this->app->bind(
            BinRoutingClient::class,
            function (Application $application) {
                return new BinRoutingClient(
                    new BinRoutingApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.binRouting.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.binRouting.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($application['config']->get('clientapis.binRouting.accessToken'))
                            ->setUsername($application['config']->get('clientapis.binRouting.username'))
                            ->setPassword($application['config']->get('clientapis.binRouting.password'))
                            ->setHost($application['config']->get('clientapis.binRouting.host'))
                            ->setUserAgent($application['config']->get('clientapis.binRouting.userAgent'))
                            ->setDebug($application['config']->get('clientapis.binRouting.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.binRouting.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.binRouting.tempFolderPath')))
                    )
                );
            }
        );
    }
}
