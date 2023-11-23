<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProbillerNG\PaymentTemplateServiceClient\Api\PaymentTemplateCommandsApi;
use ProbillerNG\PaymentTemplateServiceClient\Api\PaymentTemplateServiceApi;
use ProbillerNG\PaymentTemplateServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService as PaymentTranslatingServiceInterface;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrievePaymentTemplatesAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\ValidatePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\CircuitBreakerRetrievePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\CircuitBreakerRetrievePaymentTemplatesServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\CircuitBreakerValidatePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateServiceAdapter;

class PaymentTemplateServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentTranslatingServiceInterface::class,
            PaymentTemplateTranslatingService::class
        );

        $commandFactory = $this->app->make(CommandFactory::class);

        $this->app->bind(
            RetrievePaymentTemplatesAdapter::class,
            function (Application $app) use ($commandFactory) {
                return new CircuitBreakerRetrievePaymentTemplatesServiceAdapter(
                    $commandFactory,
                    $app->make(RetrievePaymentTemplatesServiceAdapter::class)
                );
            }
        );

        $this->app->bind(
            RetrievePaymentTemplateAdapter::class,
            function (Application $app) use ($commandFactory) {
                return new CircuitBreakerRetrievePaymentTemplateServiceAdapter(
                    $commandFactory,
                    $app->make(RetrievePaymentTemplateServiceAdapter::class)
                );
            }
        );

        $this->app->bind(
            ValidatePaymentTemplateAdapter::class,
            function (Application $app) use ($commandFactory) {
                return new CircuitBreakerValidatePaymentTemplateServiceAdapter(
                    $commandFactory,
                    $app->make(ValidatePaymentTemplateServiceAdapter::class)
                );
            }
        );

        $this->app->bind(
            PaymentTemplateClient::class,
            function (Application $application) {
                return new PaymentTemplateClient(
                    new PaymentTemplateServiceApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.paymentTemplate.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.paymentTemplate.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($application['config']->get('clientapis.paymentTemplate.accessToken'))
                            ->setUsername($application['config']->get('clientapis.paymentTemplate.username'))
                            ->setPassword($application['config']->get('clientapis.paymentTemplate.password'))
                            ->setHost($application['config']->get('clientapis.paymentTemplate.host'))
                            ->setUserAgent($application['config']->get('clientapis.paymentTemplate.userAgent'))
                            ->setDebug($application['config']->get('clientapis.paymentTemplate.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.paymentTemplate.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.paymentTemplate.tempFolderPath')))
                    )
                );
            }
        );

        $this->app->bind(
            PaymentTemplateCommandsApi::class,
            function (Application $application) {
                return new PaymentTemplateCommandsApi(
                    new \GuzzleHttp\Client(
                        [
                            RequestOptions::CONNECT_TIMEOUT => $application['config']
                                ->get('clientapis.paymentTemplate.connectionTimeout'),
                            RequestOptions::TIMEOUT         => $application['config']
                                ->get('clientapis.paymentTemplate.timeout'),
                        ]
                    ),
                    (new Configuration())
                        ->setAccessToken($application['config']->get('clientapis.paymentTemplate.accessToken'))
                        ->setUsername($application['config']->get('clientapis.paymentTemplate.username'))
                        ->setPassword($application['config']->get('clientapis.paymentTemplate.password'))
                        ->setHost($application['config']->get('clientapis.paymentTemplate.host'))
                        ->setUserAgent($application['config']->get('clientapis.paymentTemplate.userAgent'))
                        ->setDebug($application['config']->get('clientapis.paymentTemplate.debug'))
                        ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.paymentTemplate.debugFile')))
                        ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.paymentTemplate.tempFolderPath')))
                );
            }
        );
    }
}
