<?php

namespace App\Providers;

use App\Exceptions\BadGateway;
use Barryvdh\Cors\ServiceProvider;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors\CorsService as CorsSettingsService;
use Illuminate\Support\Facades\Log;

class CorsServiceProvider extends ServiceProvider
{

    /**
     * @return void
     * @throws BadGateway
     */
    public function register()
    {
        try {
            /**
             * @var $configService CorsSettingsService
             */
            $corsSettingsService = $this->app->make(CorsSettingsService::class);
            $this->loadAllowedOriginsOnConfig($corsSettingsService);

            parent::register();
        } catch (\Throwable $throwable) {
            throw new BadGateway($throwable->getMessage());
        }
    }

    /**
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * @param CorsSettingsService $corsSettingsService Cors settings service
     *
     * @return void
     * @throws \Exception
     */
    protected function loadAllowedOriginsOnConfig(CorsSettingsService $corsSettingsService): void
    {
        $allAllowedOrigins = array_merge(config('cors.allowedOrigins'), $corsSettingsService->getAllowedDomains());
        config(['cors.allowedOrigins' => $allAllowedOrigins]);

        Log::info(
            'LoadAllowedOriginsOnConfig List of CORS allowed domains',
            [
                'domains' => $allAllowedOrigins,
            ]
        );
    }
}
