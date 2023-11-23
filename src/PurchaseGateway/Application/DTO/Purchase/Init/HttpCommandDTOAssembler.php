<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init;

use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;

class HttpCommandDTOAssembler implements PurchaseInitDTOAssembler
{
    /**
     * @param PurchaseInitCommandResult $purchaseInitCommandResult Process Init Command Result
     * @return PurchaseInitCommandHttpDTO
     */
    public function assemble(PurchaseInitCommandResult $purchaseInitCommandResult): PurchaseInitCommandHttpDTO
    {
        return new PurchaseInitCommandHttpDTO($purchaseInitCommandResult);
    }
}
