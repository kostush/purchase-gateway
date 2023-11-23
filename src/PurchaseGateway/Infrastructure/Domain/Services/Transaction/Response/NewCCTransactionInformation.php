<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use Exception;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class NewCCTransactionInformation extends CCTransactionInformation
{
    /**
     * NewCCTransactionInformation constructor.
     * @param RetrieveTransaction $response Api response
     * @throws Exception
     */
    public function __construct(RetrieveTransaction $response)
    {
        parent::__construct($response);
    }
}
