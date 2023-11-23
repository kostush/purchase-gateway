<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProbillerNG\BundleManagementAdminServiceClient\Api\DomainEventsApi;
use ProbillerNG\BundleManagementAdminServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleManagementAdminService as BundleManagementAdminInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\BundleManagementAdminClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\BundleManagementAdminTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\CircuitBreakerRetrieveEventsAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveBundleManagementEventsAdapter as RetrieveBundleEventsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\RetrieveBundleManagementEventsAdapter;

class BundleManagementAdminServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register(): void
    {
        $this->app->bind(
            BundleManagementAdminInterface::class,
            BundleManagementAdminTranslatingService::class
        );

        $commandFactory = $this->app->make(CommandFactory::class);

        $this->app->bind(
            RetrieveBundleEventsAdapter::class,
            function (Application $app) use ($commandFactory) {
                return new CircuitBreakerRetrieveEventsAdapter(
                    $commandFactory,
                    $app->make(RetrieveBundleManagementEventsAdapter::class)
                );
            }
        );

        $this->app->bind(
            BundleManagementAdminClient::class,
            function (Application $application) {
                return new BundleManagementAdminClient(
                    new DomainEventsApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.bundleManagementAdmin.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.bundleManagementAdmin.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($application['config']->get('clientapis.bundleManagementAdmin.accessToken'))
                            ->setUsername($application['config']->get('clientapis.bundleManagementAdmin.username'))
                            ->setPassword($application['config']->get('clientapis.bundleManagementAdmin.password'))
                            ->setHost($application['config']->get('clientapis.bundleManagementAdmin.host'))
                            ->setUserAgent($application['config']->get('clientapis.bundleManagementAdmin.userAgent'))
                            ->setDebug($application['config']->get('clientapis.bundleManagementAdmin.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.bundleManagementAdmin.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.bundleManagementAdmin.tempFolderPath')))
                    )
                );
            }
        );
    }
}
