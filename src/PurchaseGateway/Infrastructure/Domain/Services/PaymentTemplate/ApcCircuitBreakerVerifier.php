<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use Odesk\Phystrix\ApcStateStorage;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceVerifier;

class ApcCircuitBreakerVerifier implements ServiceVerifier
{
    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        $circuitOpenForRetrievePaymentTemplatesCommand = (boolean) apc_fetch(ApcStateStorage::CACHE_PREFIX . RetrievePaymentTemplatesCommand::class . ApcStateStorage::OPENED_NAME);
        $circuitOpenForRetrievePaymentTemplateCommand  = (boolean) apc_fetch(ApcStateStorage::CACHE_PREFIX . RetrievePaymentTemplateCommand::class . ApcStateStorage::OPENED_NAME);
        $circuitOpenForValidatePaymentTemplateCommand  = (boolean) apc_fetch(ApcStateStorage::CACHE_PREFIX . ValidatePaymentTemplateCommand::class . ApcStateStorage::OPENED_NAME);

        if ($circuitOpenForRetrievePaymentTemplatesCommand
            || $circuitOpenForRetrievePaymentTemplateCommand
            || $circuitOpenForValidatePaymentTemplateCommand
        ) {
            return true;
        }

        return false;
    }
}
