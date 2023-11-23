<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\ApcStateStorage;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceVerifier;

class ApcCircuitBreakerVerifier implements ServiceVerifier
{
    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        $circuitOpenForPerformTransactionCommand           = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . CreatePerformTransactionCommand::class . ApcStateStorage::OPENED_NAME
        );
        $circuitOpenForRetrieveGetTransactionDataByCommand = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . RetrieveGetTransactionDataByCommand::class . ApcStateStorage::OPENED_NAME
        );

        $circuitOpenForCompleteThreeDCommand = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . CompleteThreeDTransactionCommand::class . ApcStateStorage::OPENED_NAME
        );

        $circuitOpenAddEpochBillerInteractionCommand = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . AddEpochBillerInteractionCommand::class . ApcStateStorage::OPENED_NAME
        );

        $circuitOpenAbortTransactionCommand = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . AbortTransactionCommand::class . ApcStateStorage::OPENED_NAME
        );

        $circuitOpenPerformThirdPartyTransactionCommand = (boolean) apc_fetch(
            ApcStateStorage::CACHE_PREFIX . CreatePerformThirdPartyTransactionCommand::class . ApcStateStorage::OPENED_NAME
        );

        if ($circuitOpenForPerformTransactionCommand
            || $circuitOpenForRetrieveGetTransactionDataByCommand
            || $circuitOpenForCompleteThreeDCommand
            || $circuitOpenAddEpochBillerInteractionCommand
            || $circuitOpenAbortTransactionCommand
            || $circuitOpenPerformThirdPartyTransactionCommand
        ) {
            return true;
        }

        return false;
    }
}
