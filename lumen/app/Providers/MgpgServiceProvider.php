<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use ProbillerMGPG\ClientApi;

class MgpgServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(ClientApi::class,
            function (Application $application) {
                $clientId = config('clientapis.mgpg.aadAuth.clientId');
                $secret   = config('clientapis.mgpg.aadAuth.clientSecret');
                $env      = config('clientapis.mgpg.aadAuth.env');
                $resource = config('clientapis.mgpg.aadAuth.resource');

                return new ClientApi(new Client(['base_uri' => $env]), $clientId, $secret, $resource);
            });
    }
}
