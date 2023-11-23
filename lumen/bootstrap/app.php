<?php

use App\Providers\CCForBlackListServiceProvider;

require_once __DIR__ . '/../../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);

$app->withFacades();

$configFiles = [
    'app',
    'builder',
    'clientapis',
    'queue',
    'cache',
    'cors',
    'database',
    'doctrine',
    'migrations',
    'phystrix',
    'projectionist',
    'worker',
    'clientpostback',
];
foreach ($configFiles as $configFile) {
    $app->configure($configFile);
}

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\ResponseToken::class
]);

$app->routeMiddleware([
    'key_auth'              => App\Http\Middleware\KeyAuth::class,
    'token_auth'            => App\Http\Middleware\TokenAuth::class,
    'cors'                  => \Barryvdh\Cors\HandleCors::class,
    'trim_strings'          => App\Http\Middleware\TrimStrings::class,
    'GenerateSessionId'     => \App\Http\Middleware\GenerateSessionId::class,
    'ValidateSessionId'     => \App\Http\Middleware\ValidateSessionId::class,
    'GenerateCorrelationId' => \App\Http\Middleware\GenerateCorrelationId::class,
    'NGLogger'              => App\Http\Middleware\NGLogger::class,
    'Session'               => App\Http\Middleware\Session::class,
    'Correlation'           => App\Http\Middleware\Correlation::class,
    'sessionIdToken'        => App\Http\Middleware\SessionIdToken::class,
    'force_cascade'         => App\Http\Middleware\ForceCascade::class
]);
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(Lumen\Providers\SecurityServiceProvider::class);
// $app->register(Lumen\Providers\AuthServiceProvider::class);
// $app->register(Lumen\Providers\EventServiceProvider::class);

// Base provider for the projection library.
// It has to be loaded before any other provider, because it alters the configs
$app->register(\ProBillerNG\LumenProjection\App\Providers\InitProjectionConfigProvider::class);

//Doctrine Service Provider And Facades Register
$app->register(LaravelDoctrine\ORM\DoctrineServiceProvider::class);
$app->register(LaravelDoctrine\Migrations\MigrationsServiceProvider::class);

// providers for internal use
$app->register(\App\Providers\ApplicationServiceProvider::class);
$app->register(\App\Providers\PurchaseGatewayServiceProvider::class);
$app->register(\App\Providers\PurchaseProcessServiceProvider::class);
$app->register(\App\Providers\RepositoryServiceProvider::class);
$app->register(\App\Providers\RetrieveIntegrationEventServiceProvider::class);
$app->register(\App\Providers\SecurityServiceProvider::class);
$app->register(\App\Providers\ConfigClientProvider::class);
$app->register(\App\Providers\CorsServiceProvider::class);
$app->register(\App\Providers\RetrieveBillerTransactionServiceProvider::class);

// providers for external services
$app->register(\App\Providers\BillerMappingServiceProvider::class);
$app->register(\App\Providers\BinRoutingServiceProvider::class);
$app->register(\App\Providers\CascadeServiceProvider::class);
$app->register(\App\Providers\ThirdPartyServiceProvider::class);
$app->register(\App\Providers\FraudServiceProvider::class);
$app->register(\App\Providers\PaymentTemplateServiceProvider::class);
$app->register(\App\Providers\TransactionServiceProvider::class);
$app->register(\App\Providers\BundleServiceProvider::class);
$app->register(\App\Providers\SiteServiceProvider::class);
$app->register(\App\Providers\BundleManagementAdminServiceProvider::class);
$app->register(\ProBillerNG\LumenProjection\App\Providers\TrackingProvider::class);
$app->register(\App\Providers\PublishingServiceProvider::class);
$app->register(\App\Providers\MemberProfileGatewayServiceProvider::class);
$app->register(\App\Providers\NuDataServiceProvider::class);
$app->register(\App\Providers\MgpgServiceProvider::class);

if ($app->runningInConsole()) {
    $app->register(\App\Providers\EmailServiceProvider::class);
    $app->register(\App\Providers\CreatePurchaseIntegrationEventServiceProvider::class);
    $app->register(\ProBillerNG\LumenProjection\App\Providers\EventBuilderProvider::class);
    $app->register(\App\Providers\WorkerServiceProvider::class);
    $app->register(\App\Providers\PurchaseAdviceNotificationServiceProvider::class);
    $app->register(\App\Providers\ServiceBusCommunicationServiceProvider::class);
    $app->register(\App\Providers\SiteAdminServiceProvider::class);
    $app->register(\App\Providers\PublisherProvider::class);
}

$app->register(\App\Providers\ProjectionProvider::class);
$app->register(\ProBillerNG\EventIngestion\Application\EventIngestionServiceProvider::class);

//Doctrine Service Provider And Facades Register
$app->register(LaravelDoctrine\ORM\DoctrineServiceProvider::class);
$app->register(LaravelDoctrine\Migrations\MigrationsServiceProvider::class);

$app->register(CCForBlackListServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'ProBillerNG\PurchaseGateway\UI\Http\Controllers',
], function (\Laravel\Lumen\Routing\Router $router) {
    require __DIR__ . '/../routes/routes.php';
});

// Required for detailed exception responses
$app->translator->setLocale('en');

return $app;
