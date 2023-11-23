<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init;

use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;

interface PurchaseInitDTOAssembler
{
    /**
     * @param PurchaseInitCommandResult $purchaseInitCommandResult Process Init Command Result
     *
     * @return mixed
     */
    public function assemble(PurchaseInitCommandResult $purchaseInitCommandResult);
}
