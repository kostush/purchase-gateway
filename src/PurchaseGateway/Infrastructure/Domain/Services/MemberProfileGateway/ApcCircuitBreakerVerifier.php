<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use Odesk\Phystrix\ApcStateStorage;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceVerifier;

class ApcCircuitBreakerVerifier implements ServiceVerifier
{
    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . RetrieveMemberProfileCommand::class . ApcStateStorage::OPENED_NAME
        );
    }
}
