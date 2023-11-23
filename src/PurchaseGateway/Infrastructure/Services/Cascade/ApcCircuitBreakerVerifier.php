<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use Odesk\Phystrix\ApcStateStorage;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceVerifier;

class ApcCircuitBreakerVerifier implements ServiceVerifier
{
    /**
     * @return bool
     */
    public function isOpen() : bool
    {
        return (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . RetrieveCascadeCommand::class . ApcStateStorage::OPENED_NAME
        );
    }
}
