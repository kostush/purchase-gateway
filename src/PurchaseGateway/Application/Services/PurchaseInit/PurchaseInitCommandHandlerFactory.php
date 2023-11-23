<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit;

interface PurchaseInitCommandHandlerFactory
{
    /**
     * @param string|null $memberId MemberId
     * @return BaseInitCommandHandler
     */
    public function getHandler(?string $memberId): BaseInitCommandHandler;
}
