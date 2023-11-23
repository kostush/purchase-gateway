<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;

interface ProcessPurchaseDTOAssembler
{
    /**
     * @param GenericPurchaseProcess $purchaseProcess PurchaseProcess
     * @return ProcessPurchaseHttpDTO
     */
    public function assemble(GenericPurchaseProcess $purchaseProcess): ProcessPurchaseHttpDTO;
}
