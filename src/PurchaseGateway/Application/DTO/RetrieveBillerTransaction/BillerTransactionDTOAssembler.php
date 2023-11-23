<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

interface BillerTransactionDTOAssembler
{
    /**
     * @param RetrieveTransactionResult $transaction Transaction
     * @return mixed
     */
    public function assemble(RetrieveTransactionResult $transaction);
}
