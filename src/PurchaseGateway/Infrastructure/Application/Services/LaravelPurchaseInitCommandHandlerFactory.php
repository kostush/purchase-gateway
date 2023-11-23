<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Laravel\Lumen\Application;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\ExistingMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandHandlerFactory;

class
LaravelPurchaseInitCommandHandlerFactory implements PurchaseInitCommandHandlerFactory
{
    /**
     * @var Application
     */
    private $app;

    /**
     * LaravelPurchaseCommandHandlerFactory constructor.
     * @param Application $app Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string|null $memberId MemberId
     * @return BaseInitCommandHandler
     */
    public function getHandler(?string $memberId): BaseInitCommandHandler
    {
        if (!empty($memberId)) {
            return $this->app->make(ExistingMemberInitCommandHandler::class);
        }

        return $this->app->make(NewMemberInitCommandHandler::class);
    }
}
