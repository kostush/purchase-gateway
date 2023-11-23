<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProbillerNG\PurchaseAdviceNotificationServiceClient\Api\PurchaseAdviceNotificationApi;
use ProBillerNG\PurchaseGateway\Domain\Services\AdviceNotificationAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseAdviceNotificationService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\CircuitBreakerAdviceNotificationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\PurchaseAdviceNotificationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\PurchaseAdviceNotificationClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\PurchaseAdviceNotificationClientConfiguration;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\PurchaseAdviceNotificationTranslatingService;

class PurchaseAdviceNotificationServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            PurchaseAdviceNotificationService::class,
            PurchaseAdviceNotificationTranslatingService::class
        );

        $this->app->bind(
            PurchaseAdviceNotificationClient::class,
            function (Application $application) {
                return new PurchaseAdviceNotificationClient(
                    new PurchaseAdviceNotificationApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.adviceNotification.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.adviceNotification.timeout'),
                            ]
                        ),
                        (new PurchaseAdviceNotificationClientConfiguration())
                            ->setAccessToken($application['config']->get('clientapis.adviceNotification.accessToken'))
                            ->setUsername($application['config']->get('clientapis.adviceNotification.username'))
                            ->setPassword($application['config']->get('clientapis.adviceNotification.password'))
                            ->setHost($application['config']->get('clientapis.adviceNotification.host'))
                            ->setUserAgent($application['config']->get('clientapis.adviceNotification.userAgent'))
                            ->setDebug($application['config']->get('clientapis.adviceNotification.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.adviceNotification.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.adviceNotification.tempFolderPath')))
                    )
                );
            }
        );

        $this->app->bind(
            AdviceNotificationAdapter::class,
            function () {
                return new CircuitBreakerAdviceNotificationAdapter(
                    $this->app->make(CommandFactory::class),
                    $this->app->make(PurchaseAdviceNotificationAdapter::class)
                );
            }
        );
    }
}
