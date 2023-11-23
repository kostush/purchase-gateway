<?php
declare(strict_types=1);

namespace App\Providers;

use CommonServices\FraudServiceClient\Api\AdviceApi;
use CommonServices\FraudServiceClient\Configuration as FraudServiceCsConfiguration;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProbillerNG\FraudServiceClient\Api\FraudServiceApi;
use ProbillerNG\FraudServiceClient\Configuration;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingCardOnProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingMemberOnInit;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForNewMemberOnInit;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\PaymentTemplateValidation\PaymentTemplateValidationTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\CircuitBreakerFraudAdviceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\CircuitBreakerFraudAdviceCsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\CircuitBreakerFraudRecommendation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\FraudRecommendationClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForExistingCardOnProcessTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForExistingMemberOnInitTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewMemberOnInitTranslatingService;

class FraudServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            FraudService::class,
            FraudAdviceTranslatingService::class
        );

        $this->app->bind(
            FraudAdviceClient::class,
            function (Application $application) {
                return new FraudAdviceClient(
                    new FraudServiceApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.fraud.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.fraud.timeout'),
                            ]
                        ),
                        (new Configuration())
                            ->setAccessToken($application['config']->get('clientapis.fraud.accessToken'))
                            ->setUsername($application['config']->get('clientapis.fraud.username'))
                            ->setPassword($application['config']->get('clientapis.fraud.password'))
                            ->setHost($application['config']->get('clientapis.fraud.host'))
                            ->setUserAgent($application['config']->get('clientapis.fraud.userAgent'))
                            ->setDebug($application['config']->get('clientapis.fraud.debug'))
                            ->setDebugFile(
                                storage_path('logs/' . $application['config']->get('clientapis.fraud.debugFile'))
                            )
                            ->setTempFolderPath(
                                storage_path(
                                    'logs/' . $application['config']->get('clientapis.fraud.tempFolderPath')
                                )
                            )
                    )
                );
            }
        );

        $this->app->bind(
            FraudAdapter::class,
            function () {
                return new CircuitBreakerFraudAdviceAdapter(
                    $this->app->make(CommandFactory::class),
                    $this->app->make(FraudAdviceAdapter::class)
                );
            }
        );

        /**
         * FraudServiceCs replaced by Config Service call but we are keeping same interface as it's injected on handler
         * But we switched the previous concrete(FraudAdviceCsTranslatingService) class to
         * (PaymentTemplateValidationTranslatingService) new one which class is calling to config service
         */
        $this->app->bind(
            FraudCsService::class,
            PaymentTemplateValidationTranslatingService::class
        );

        $this->app->bind(
            FraudAdviceCsClient::class,
            function (Application $application) {
                return new FraudAdviceCsClient(
                    new AdviceApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.fraudServiceCs.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.fraudServiceCs.timeout'),
                            ]
                        ),
                        (new FraudServiceCsConfiguration())
                            ->setApiKeyPrefix('Authorization', 'Bearer')
                            ->setUsername($application['config']->get('clientapis.fraudServiceCs.username'))
                            ->setPassword($application['config']->get('clientapis.fraudServiceCs.password'))
                            ->setHost($application['config']->get('clientapis.fraudServiceCs.host'))
                            ->setUserAgent($application['config']->get('clientapis.fraudServiceCs.userAgent'))
                            ->setDebug($application['config']->get('clientapis.fraudServiceCs.debug'))
                            ->setDebugFile(
                                storage_path(
                                    'logs/' . $application['config']->get('clientapis.fraudServiceCs.debugFile')
                                )
                            )
                            ->setTempFolderPath(
                                storage_path(
                                    'logs/' . $application['config']->get('clientapis.fraudServiceCs.tempFolderPath')
                                )
                            )
                    )
                );
            }
        );

        $this->app->bind(
            FraudRecommendationClient::class,
            function (Application $application) {
                return new FraudRecommendationClient(
                    new AdviceApi(
                        new \GuzzleHttp\Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.fraudServiceCs.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.fraudServiceCs.timeout'),
                            ]
                        ),
                        (new FraudServiceCsConfiguration())
                            ->setApiKeyPrefix('Authorization', 'Bearer')
                            ->setUsername($application['config']->get('clientapis.fraudServiceCs.username'))
                            ->setPassword($application['config']->get('clientapis.fraudServiceCs.password'))
                            ->setHost($application['config']->get('clientapis.fraudServiceCs.host'))
                            ->setUserAgent($application['config']->get('clientapis.fraudServiceCs.userAgent'))
                            ->setDebug($application['config']->get('clientapis.fraudServiceCs.debug'))
                            ->setDebugFile(
                                storage_path(
                                    'logs/' . $application['config']->get('clientapis.fraudServiceCs.debugFile')
                                )
                            )
                            ->setTempFolderPath(
                                storage_path(
                                    'logs/' . $application['config']->get('clientapis.fraudServiceCs.tempFolderPath')
                                )
                            )
                    )
                );
            }
        );

        $this->app->bind(
            FraudCsAdapter::class,
            function () {
                return new CircuitBreakerFraudAdviceCsAdapter(
                    $this->app->make(CommandFactory::class),
                    $this->app->make(FraudAdviceCsAdapter::class)
                );
            }
        );

        $this->app->bind(
            RetrieveFraudRecommendationForNewMemberOnInit::class,
            RetrieveFraudRecommendationForNewMemberOnInitTranslatingService::class
        );

        $this->app->bind(
            RetrieveFraudRecommendationForExistingMemberOnInit::class,
            RetrieveFraudRecommendationForExistingMemberOnInitTranslatingService::class
        );

        $this->app->bind(
            RetrieveFraudRecommendationForExistingCardOnProcess::class,
            RetrieveFraudRecommendationForExistingCardOnProcessTranslatingService::class
        );

        $this->app->bind(
            FraudRecommendationAdapter::class,
            function () {
                return new CircuitBreakerFraudRecommendation(
                    $this->app->make(CommandFactory::class),
                    $this->app->make(RetrieveFraudRecommendationAdapter::class)
                );
            }
        );
    }
}
