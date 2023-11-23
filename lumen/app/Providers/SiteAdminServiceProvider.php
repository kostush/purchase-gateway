<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Odesk\Phystrix\CommandFactory;
use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\CircuitBreakerRetrieveEventsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\RetrieveSiteAdminEventsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\SiteAdminClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\SiteAdminTranslatingService;
use ProbillerNG\SiteAdminServiceClient\Api\DomainEventsApi;
use ProbillerNG\SiteAdminServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Domain\Services\SiteAdminService as SiteAdminInterface;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveSiteAdminEventsAdapter as RetrieveSiteAdminEventsInterface;

class SiteAdminServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            SiteAdminInterface::class,
            SiteAdminTranslatingService::class
        );

        $this->app->bind(
            RetrieveSiteAdminEventsInterface::class,
            function (Application $app) {
                return new CircuitBreakerRetrieveEventsAdapter(
                    $app->make(CommandFactory::class),
                    $app->make(RetrieveSiteAdminEventsAdapter::class)
                );
            }
        );

        $this->app->bind(
            SiteAdminClient::class,
            function (Application $app) {
                return new SiteAdminClient(
                    new DomainEventsApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $app['config']
                                    ->get('clientapis.siteAdmin.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $app['config']
                                    ->get('clientapis.siteAdmin.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($app['config']->get('clientapis.siteAdmin.accessToken'))
                            ->setUsername($app['config']->get('clientapis.siteAdmin.username'))
                            ->setPassword($app['config']->get('clientapis.siteAdmin.password'))
                            ->setHost($app['config']->get('clientapis.siteAdmin.host'))
                            ->setUserAgent($app['config']->get('clientapis.siteAdmin.userAgent'))
                            ->setDebug($app['config']->get('clientapis.siteAdmin.debug'))
                            ->setDebugFile(storage_path('logs/' . $app['config']->get('clientapis.siteAdmin.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $app['config']->get('clientapis.siteAdmin.tempFolderPath')))
                    )
                );
            }
        );
    }
}
