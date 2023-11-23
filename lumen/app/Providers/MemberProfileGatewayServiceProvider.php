<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProbillerNG\MemberProfileGatewayClient\Configuration;
use ProbillerNG\MemberProfileGatewayClient\Api\MemberProfileApi;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveMemberProfileAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\CircuitBreakerRetrieveMemberProfileAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\MemberProfileGatewayClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\MemberProfileGatewayTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\RetrieveMemberProfileServiceAdapter;

class MemberProfileGatewayServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            MemberProfileGatewayService::class,
            MemberProfileGatewayTranslatingService::class
        );

        $this->app->bind(
            RetrieveMemberProfileAdapter::class,
            function (Application $app) {
                return new CircuitBreakerRetrieveMemberProfileAdapter(
                    $app->make(CommandFactory::class),
                    $app->make(RetrieveMemberProfileServiceAdapter::class)
                );
            }
        );

        $this->app->bind(
            MemberProfileGatewayClient::class,
            function (Application $application) {
                return new MemberProfileGatewayClient(
                    new MemberProfileApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.memberProfileGateway.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.memberProfileGateway.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($application['config']->get('clientapis.memberProfileGateway.accessToken'))
                            ->setUsername($application['config']->get('clientapis.memberProfileGateway.username'))
                            ->setPassword($application['config']->get('clientapis.memberProfileGateway.password'))
                            ->setHost($application['config']->get('clientapis.memberProfileGateway.host'))
                            ->setUserAgent($application['config']->get('clientapis.memberProfileGateway.userAgent'))
                            ->setDebug($application['config']->get('clientapis.memberProfileGateway.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.memberProfileGateway.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.memberProfileGateway.tempFolderPath')))
                    )
                );
            }
        );
    }
}
