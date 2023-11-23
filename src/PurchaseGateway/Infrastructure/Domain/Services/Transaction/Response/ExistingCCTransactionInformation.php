<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use Exception;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;

class ExistingCCTransactionInformation extends CCTransactionInformation
{
    /**
     * @var string|null
     */
    private $cardHash;

    /**
     * ExistingCCTransactionInformation constructor.
     * @param RetrieveTransaction $response Api response
     * @throws Exception
     */
    public function __construct(RetrieveTransaction $response)
    {
        parent::__construct($response);

        $this->cardHash = $response->getCardHash();
    }

    /**
     * @return string|null
     */
    public function cardHash(): ?string
    {
        return $this->cardHash;
    }
}
