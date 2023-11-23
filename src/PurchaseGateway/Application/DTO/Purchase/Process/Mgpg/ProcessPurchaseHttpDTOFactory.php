<?php

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseThreedDto;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\RebillUpdateProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class ProcessPurchaseHttpDTOFactory
{
    /**
     * @param GenericPurchaseProcess $purchaseProcess Purchase process object
     * @param TokenGenerator         $tokenGenerator  Token generator
     * @param Site                   $site            Site
     * @param CryptService           $cryptService    Crypt Service
     * @return ProcessPurchaseHttpDTO
     */
    public static function create(
        GenericPurchaseProcess $purchaseProcess,
        TokenGenerator $tokenGenerator,
        Site $site,
        CryptService $cryptService
    ): ProcessPurchaseHttpDTO {

        if ($purchaseProcess instanceof RebillUpdateProcess && !$purchaseProcess->isFraud()) {
            return new RebillUpdateHttpDto(
                $purchaseProcess,
                $tokenGenerator,
                $site,
                $cryptService
            );
        }

        if ($purchaseProcess instanceof RebillUpdateProcess && $purchaseProcess->isFraud()) {
            return new RebillUpdateFraudHttpDto(
                $purchaseProcess,
                $tokenGenerator,
                $site,
                $cryptService
            );
        }

        if ($purchaseProcess->isFraud()) {
            return new ProcessPurchaseFraudHttpDTO(
                $purchaseProcess,
                $tokenGenerator,
                $site
            );
        }

        if ($purchaseProcess instanceof PurchaseProcess && $purchaseProcess->isGiftcardsCompleteProcess()) {
            return new ProcessPurchaseGiftcardsCompleteHttpDTO(
                $purchaseProcess,
                $tokenGenerator,
                $site,
                $cryptService
            );
        }

        // If no purchase is on the MGPG PurchaseProcess we are dealing with an MGPG response that only
        // contains a `nextAction` object(unlike NG which always has invoice information)
        if ($purchaseProcess->purchase() == null) {
            return new ProcessPurchaseThreedDto($purchaseProcess, $tokenGenerator, $site, $cryptService);
        }

        return new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $tokenGenerator,
            $site,
            $cryptService
        );
    }
}
