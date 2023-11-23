<?php

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class ProcessPurchaseHttpDTOFactory
{
    /**
     * @param PurchaseProcess $purchaseProcess Purchase process object
     * @param TokenGenerator  $tokenGenerator  Token generator
     * @param Site            $site            Site
     * @param CryptService    $cryptService    Crypt Service
     * @return ProcessPurchaseHttpDTO
     */
    public static function create(
        PurchaseProcess $purchaseProcess,
        TokenGenerator $tokenGenerator,
        Site $site,
        CryptService $cryptService
    ): ProcessPurchaseHttpDTO {
        if ($purchaseProcess->isFraud()) {
            return new ProcessPurchaseFraudHttpDTO(
                $purchaseProcess,
                $tokenGenerator,
                $site
            );
        }

        return new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $tokenGenerator,
            $site,
            $cryptService
        );
    }
}