<?php
declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use Odesk\Phystrix\CommandFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\AddEpochBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\AddQyssoBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\NewChequePerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\CompleteThreeDInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\ExistingCardPerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\NewCardPerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformLookupThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformQyssoRebillTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformThirdPartyTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\SimplifiedCompleteThreeDInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionTranslatingService as TransactionTranslatingServiceInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Model\Services\Transaction\TransactionClientConfiguration;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddEpochBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddQyssoBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerAddEpochBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerNewChequeTransactionServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerPerformAbortTransactionServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerCompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerExistingCardTransactionServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerGetTransactionDataByServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerNewCardTransactionServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerPerformLookupThreeDThreeDTransactionServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerSimplifiedCompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CircuitBreakerThirdPartyTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\LookupThreeDThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewCardPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ExistingCardPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\GetTransactionDataByAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\PerformQyssoRebillTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewChequePerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\SimplifiedCompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ThirdPartyPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProbillerNG\TransactionServiceClient\Api\EpochApi;
use ProbillerNG\TransactionServiceClient\Api\NetbillingApi;
use ProbillerNG\TransactionServiceClient\Api\QyssoApi;
use ProbillerNG\TransactionServiceClient\Api\RocketgateApi;
use ProbillerNG\TransactionServiceClient\Api\TransactionApi;

class TransactionServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->app->bind(
            TransactionTranslatingServiceInterface::class,
            TransactionTranslatingService::class
        );

        $this->app->bind(
            TransactionServiceClient::class,
            function (Application $application) {
                return new TransactionServiceClient(
                    new TransactionApi(
                        new Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.transaction.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.transaction.timeout'),
                            ]
                        ),
                        (new TransactionClientConfiguration())
                            ->setAccessToken($application['config']->get('clientapis.transaction.accessToken'))
                            ->setUsername($application['config']->get('clientapis.transaction.username'))
                            ->setPassword($application['config']->get('clientapis.transaction.password'))
                            ->setHost($application['config']->get('clientapis.transaction.host'))
                            ->setUserAgent($application['config']->get('clientapis.transaction.userAgent'))
                            ->setDebug($application['config']->get('clientapis.transaction.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.transaction.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.transaction.tempFolderPath')))
                    ),
                    new NetbillingApi(
                        new Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.transaction.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.transaction.timeout'),
                            ]
                        ),
                        (new TransactionClientConfiguration())
                            ->setAccessToken($application['config']->get('clientapis.transaction.accessToken'))
                            ->setUsername($application['config']->get('clientapis.transaction.username'))
                            ->setPassword($application['config']->get('clientapis.transaction.password'))
                            ->setHost($application['config']->get('clientapis.transaction.host'))
                            ->setUserAgent($application['config']->get('clientapis.transaction.userAgent'))
                            ->setDebug($application['config']->get('clientapis.transaction.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.transaction.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.transaction.tempFolderPath')))
                    ),
                    new EpochApi(
                        new Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.transaction.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.transaction.timeout'),
                            ]
                        ),
                        (new TransactionClientConfiguration())
                            ->setAccessToken($application['config']->get('clientapis.transaction.accessToken'))
                            ->setUsername($application['config']->get('clientapis.transaction.username'))
                            ->setPassword($application['config']->get('clientapis.transaction.password'))
                            ->setHost($application['config']->get('clientapis.transaction.host'))
                            ->setUserAgent($application['config']->get('clientapis.transaction.userAgent'))
                            ->setDebug($application['config']->get('clientapis.transaction.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.transaction.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.transaction.tempFolderPath')))
                    ),
                    new QyssoApi(
                        new Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.transaction.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.transaction.timeout'),
                            ]
                        ),
                        (new TransactionClientConfiguration())
                            ->setAccessToken($application['config']->get('clientapis.transaction.accessToken'))
                            ->setUsername($application['config']->get('clientapis.transaction.username'))
                            ->setPassword($application['config']->get('clientapis.transaction.password'))
                            ->setHost($application['config']->get('clientapis.transaction.host'))
                            ->setUserAgent($application['config']->get('clientapis.transaction.userAgent'))
                            ->setDebug($application['config']->get('clientapis.transaction.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.transaction.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.transaction.tempFolderPath')))
                    ),
                    new RocketgateApi(
                        new Client(
                            [
                                RequestOptions::CONNECT_TIMEOUT => $application['config']
                                    ->get('clientapis.transaction.connectionTimeout'),
                                RequestOptions::TIMEOUT         => $application['config']
                                    ->get('clientapis.transaction.timeout'),
                            ]
                        ),
                        (new TransactionClientConfiguration())
                            ->setAccessToken($application['config']->get('clientapis.transaction.accessToken'))
                            ->setUsername($application['config']->get('clientapis.transaction.username'))
                            ->setPassword($application['config']->get('clientapis.transaction.password'))
                            ->setHost($application['config']->get('clientapis.transaction.host'))
                            ->setUserAgent($application['config']->get('clientapis.transaction.userAgent'))
                            ->setDebug($application['config']->get('clientapis.transaction.debug'))
                            ->setDebugFile(storage_path('logs/' . $application['config']->get('clientapis.transaction.debugFile')))
                            ->setTempFolderPath(storage_path('logs/' . $application['config']->get('clientapis.transaction.tempFolderPath')))
                    )
                );
            }
        );

        $commandFactory = $this->app->make(CommandFactory::class);

        $this->app->bind(
            NewCardPerformTransactionInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerNewCardTransactionServiceAdapter(
                    $commandFactory,
                    $this->app->make(NewCardPerformTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            ExistingCardPerformTransactionInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerExistingCardTransactionServiceAdapter(
                    $commandFactory,
                    $this->app->make(ExistingCardPerformTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            GetTransactionDataByInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerGetTransactionDataByServiceAdapter(
                    $commandFactory,
                    $this->app->make(GetTransactionDataByAdapter::class)
                );
            }
        );

        $this->app->bind(
            CompleteThreeDInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerCompleteThreeDTransactionAdapter(
                    $commandFactory,
                    $this->app->make(CompleteThreeDTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            SimplifiedCompleteThreeDInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerSimplifiedCompleteThreeDTransactionAdapter(
                    $commandFactory,
                    $this->app->make(SimplifiedCompleteThreeDTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            AddEpochBillerInteractionInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerAddEpochBillerInteractionAdapter(
                    $commandFactory,
                    $this->app->make(AddEpochBillerInteractionAdapter::class)
                );
            }
        );

        $this->app->bind(
            AddQyssoBillerInteractionInterfaceAdapter::class,
            AddQyssoBillerInteractionAdapter::class
        );

        $this->app->bind(
            PerformQyssoRebillTransactionInterfaceAdapter::class,
            PerformQyssoRebillTransactionAdapter::class
        );

        $this->app->bind(
            PerformThirdPartyTransactionAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerThirdPartyTransactionAdapter(
                    $commandFactory,
                    $this->app->make(ThirdPartyPerformTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            PerformAbortTransactionAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerPerformAbortTransactionServiceAdapter(
                    $commandFactory,
                    $this->app->make(AbortTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            PerformLookupThreeDTransactionAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerPerformLookupThreeDThreeDTransactionServiceAdapter(
                    $commandFactory,
                    $this->app->make(LookupThreeDThreeDTransactionAdapter::class)
                );
            }
        );

        $this->app->bind(
            NewChequePerformTransactionInterfaceAdapter::class,
            function () use ($commandFactory) {
                return new CircuitBreakerNewChequeTransactionServiceAdapter(
                    $commandFactory,
                    $this->app->make(NewChequePerformTransactionAdapter::class)
                );
            }
        );
    }
}
