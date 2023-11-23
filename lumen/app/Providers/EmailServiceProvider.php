<?php
declare(strict_types=1);

namespace App\Providers;

use Barryvdh\Cors\ServiceProvider;
use CommonServices\EmailServiceClient\Api\EmailApi;
use GuzzleHttp\RequestOptions;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\CircuitBreakerEmailServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceClient;

class EmailServiceProvider extends ServiceProvider
{
    const CACHE_KEY = 'ngAuthTokenCSES';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            \ProBillerNG\PurchaseGateway\Domain\Services\EmailService::class,
            EmailService::class
        );

        $this->app->bind(
            \ProBillerNG\PurchaseGateway\Domain\Services\EmailAdapter::class,
            function () {
                return new CircuitBreakerEmailServiceAdapter(
                    $this->app->make(CommandFactory::class),
                    $this->app->make(EmailServiceAdapter::class)
                );
            }
        );

        $this->app->bind(
            EmailServiceClient::class,
            function (Application $application) {

                return new EmailServiceClient(
                    new EmailApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.emailService.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.emailService.timeout'),
                            ]
                        ),
                        (new EmailConfigService())
                            ->setApiKeyPrefix('Authorization', 'Bearer')
                            ->setUsername($application['config']->get('clientapis.emailService.username'))
                            ->setPassword($application['config']->get('clientapis.emailService.password'))
                            ->setHost($application['config']->get('clientapis.emailService.host'))
                            ->setUserAgent($application['config']->get('clientapis.emailService.userAgent'))
                            ->setDebug($application['config']->get('clientapis.emailService.debug'))
                            ->setDebugFile(
                                storage_path(
                                    'logs/' . $application['config']->get('clientapis.emailService.debugFile')
                                )
                            )
                            ->setTempFolderPath(
                                storage_path(
                                    'logs/' .
                                    $application['config']->get('clientapis.emailService.tempFolderPath')
                                )
                            )
                    )
                );
            }
        );
    }
}
