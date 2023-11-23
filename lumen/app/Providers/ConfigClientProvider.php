<?php
declare(strict_types=1);

namespace App\Providers;

use Grpc\ChannelCredentials;
use Illuminate\Support\ServiceProvider;
use Probiller\Service\Config\ProbillerConfigClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors\CorsService;

/**
 * Class ConfigClientProvider
 * @package App\Providers
 */
class ConfigClientProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $credentials = env('CONFIG_SERVICE_USE_SSL', true)
            ? ChannelCredentials::createSsl() : ChannelCredentials::createInsecure();

        $client = new ProbillerConfigClient(
            env('CONFIG_SERVICE_HOST', 'host.docker.internal:5000'),
            ['credentials' => $credentials]
        );

        $this->app->bind(
            ConfigService::class,
            function () use ($client) {
                return new ConfigService(
                    $client
                );
            }
        );

        $this->app->bind(
            CorsService::class,
            function () use ($client) {
                return new CorsService(
                    $client
                );
            }
        );

    }
}