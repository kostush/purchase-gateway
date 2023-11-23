<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Application\Services\AuthenticateKey;
use ProBillerNG\PurchaseGateway\Application\Services\AuthenticateToken;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\SessionToken;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\TokenInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AuthenticateJsonWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AuthenticateKeyTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SessionWebToken;

/**
 * Class SecurityServiceProvider
 * @package App\Providers
 */
class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(AuthenticateToken::class, AuthenticateJsonWebToken::class);
        $this->app->bind(AuthenticateKey::class, AuthenticateKeyTranslatingService::class);
        $this->app->bind(TokenGenerator::class, JsonWebTokenGenerator::class);
        $this->app->bind(TokenInterface::class, JsonWebToken::class);
        $this->app->bind(CryptService::class, SodiumCryptService::class);
        $this->app->bind(SessionToken::class, SessionWebToken::class);
    }
}
